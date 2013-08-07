<?php

namespace Flow;

abstract class Template
{
    protected $loader;
    protected $helpers;
    protected $parent;
    protected $blocks;
    protected $macros;
    protected $imports;
    protected $stack;

    public function __construct($loader, $helpers = array())
    {
        $this->loader  = $loader;
        $this->helpers = $helpers;
        $this->parent  = null;
        $this->blocks  = array();
        $this->macros  = array();
        $this->imports = array();
        $this->stack   = array();
    }

    public function loadExtends($template)
    {
        try {
            return $this->loader->load($template, static::NAME);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'error extending %s (%s) from %s line %d',
                $template, $e->getMessage(), static::NAME,
                $this->getLineTrace($e)
            ));
        }
    }

    public function loadInclude($template)
    {
        try {
            return $this->loader->load($template, static::NAME);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'error including %s (%s) from %s line %d',
                $template, $e->getMessage(), static::NAME,
                $this->getLineTrace($e)
            ));
        }
    }

    public function loadImport($template)
    {
        try {
            return $this->loader->load($template, static::NAME);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'error importing %s (%s) from %s line %d',
                $template, $e->getMessage(), static::NAME,
                $this->getLineTrace($e)
            ));
        }
    }

    public function displayBlock($name, $context, $blocks, $macros)
    {
        $blocks = $blocks + $this->blocks;
        if (isset($blocks[$name]) && is_callable($blocks[$name])) {
            return call_user_func($blocks[$name], $context, $blocks, $macros);
        }
    }

    public function displayParent($name, $context, $blocks, $macros)
    {
        $parent = $this;
        while ($parent = $parent->parent) {
            if (isset($parent->blocks[$name]) &&
                is_callable($parent->blocks[$name])) {
                return call_user_func($parent->blocks[$name], $context, $blocks,
                        $macros);
            }
        }
    }

    public function expandMacro($name, $context, $macros)
    {
        $macros = $macros + $this->macros;
        if (isset($macros[$name]) && is_callable($macros[$name])) {
            return call_user_func($macros[$name], $context, $macros);
        } else {
            throw new \RuntimeException(
                sprintf(
                    'undefined macro "%s" in %s line %d',
                    $name, static::NAME, $this->getLineTrace()
                )
            );
        }
    }

    public function pushContext(&$context, $name)
    {
        if (!array_key_exists($name, $this->stack)) {
            $this->stack[$name] = array();
        }
        array_push($this->stack[$name], isset($context[$name]) ?
            $context[$name] : null
        );
        return $this;
    }

    public function popContext(&$context, $name)
    {
        if (!empty($this->stack[$name])) {
            $context[$name] = array_pop($this->stack[$name]);
        }
        return $this;
    }

    public function getLineTrace(\Exception $e = null)
    {
        if (!isset($e)) {
            $e = new \Exception;
        }

        $lines = static::$lines;

        $file = get_class($this) . '.php';

        foreach ($e->getTrace() as $trace) {
            if (isset($trace['file']) && basename($trace['file']) == $file) {
                $line = $trace['line'];
                return isset($lines[$line]) ? $lines[$line] : null;
            }
        }
        return null;
    }

    public function helper($name, $args = array())
    {
        $args = func_get_args();
        $name = array_shift($args);

        try {
            if (isset($this->helpers[$name]) &&
                is_callable($this->helpers[$name])) {
                return call_user_func_array($this->helpers[$name], $args);
            } elseif (is_callable("\\Flow\\Helper\\$name")) {
                return call_user_func_array("\\Flow\\Helper\\$name", $args);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf(
                    '%s in %s line %d',
                    $e->getMessage(), static::NAME, $this->getLineTrace($e)
                )
            );
        }

        throw new \RuntimeException(
            sprintf(
                'undefined helper "%s" in %s line %d',
                $name, static::NAME, $this->getLineTrace()
            )
        );

    }

    abstract public function display($context = array(), $blocks = array(),
        $macros = array());

    public function render($context = array(), $blocks = array(),
        $macros = array())
    {
        ob_start();
        $this->display($context, $blocks, $macros);
        return ob_get_clean();
    }

    public function iterate($context, $seq)
    {
        return new Helper\ContextIterator($seq, isset($context['loop']) ?
            $context['loop'] : null);
    }

    public function getAttr($obj, $attr, $args = array())
    {
        if (is_array($obj)) {
            if (isset($obj[$attr])) {
                if ($obj[$attr] instanceof \Closure) {
                    if (is_array($args)) {
                        array_unshift($args, $obj);
                    } else {
                        $args = array($obj);
                    }
                    return call_user_func_array($obj[$attr], $args);
                } else {
                    return $obj[$attr];
                }
            } else {
                return null;
            }
        } elseif (is_object($obj)) {

            if (is_array($args)) {
                $callable = array($obj, $attr);
                return is_callable($callable) ?
                    call_user_func_array($callable, $args) : null;
            } else {
                $members = array_keys(get_object_vars($obj));
                $methods = get_class_methods(get_class($obj));
                if (in_array($attr, $members)) {
                    return @$obj->$attr;
                } elseif (in_array('__get', $methods)) {
                    return $obj->__get($attr);
                } else {
                    $callable = array($obj, $attr);
                    return is_callable($callable) ?
                        call_user_func($callable) : null;
                }
            }

        } else {
            return null;
        }
    }

    public function setAttr(&$obj, $attrs, $value)
    {
        if (empty($attrs)) {
            $obj = $value;
            return;
        }
        $attr = array_shift($attrs);
        if (is_object($obj)) {
            $class = get_class($obj);
            $members = array_keys(get_object_vars($obj));
            if (!in_array($attr, $members)) {
                if (empty($attrs) && method_exists($obj, '__set')) {
                    $obj->__set($attr, $value);
                    return;
                } elseif (property_exists($class, $attr)) {
                    throw new \RuntimeException(
                        "inaccessible '$attr' object attribute"
                    );
                } else {
                    if ($attr === null || $attr === false || $attr === '') {
                        if ($attr === null)  $token = 'null';
                        if ($attr === false) $token = 'false';
                        if ($attr === '')    $token = 'empty string';
                        throw new \RuntimeException(
                            sprintf(
                                'invalid object attribute (%s) in %s line %d',
                                $token, static::NAME, $this->getLineTrace()
                            )
                        );
                    }
                    $obj->{$attr} = null;
                }
            }
            if (!isset($obj->$attr)) $obj->$attr = null;
            $this->setAttr($obj->$attr, $attrs, $value);
        } else {
            if (!is_array($obj)) $obj = array();
            $this->setAttr($obj[$attr], $attrs, $value);
        }
    }
}

namespace Flow\Helper;

class ContextIterator implements \Iterator
{
    protected $sequence;

    public function __construct($sequence, $parent)
    {
        if ($sequence instanceof \Traversable) {
            $this->length = ($sequence instanceof \Countable) ?
                count($sequence) : iterator_count($sequence);
            $this->sequence = $sequence;
        } elseif (is_array($sequence)) {
            $this->length = count($sequence);
            $this->sequence = new \ArrayIterator($sequence);
        } else {
            $this->length = 0;
            $this->sequence = new \ArrayIterator;
        }
        $this->parent = $parent;
    }

    public function rewind()
    {
        $this->sequence->rewind();

        $this->index = 0;
        $this->count = $this->index + 1;
        $this->first = $this->count == 1;
        $this->last  = $this->count == $this->length;
    }

    public function key()
    {
        return $this->sequence->key();
    }

    public function valid()
    {
        return $this->sequence->valid();
    }

    public function next()
    {
        $this->sequence->next();

        $this->index += 1;
        $this->count  = $this->index + 1;
        $this->first  = $this->count == 1;
        $this->last   = $this->count == $this->length;
    }

    public function current()
    {
        return $this->sequence->current();
    }
}

class RangeIterator implements \Iterator
{
    protected $lower;
    protected $upper;
    protected $step;
    protected $current;

    public function __construct($lower, $upper, $step = 1)
    {
        $this->lower = $lower;
        $this->upper = $upper;
        $this->step = $step;
    }

    public function length()
    {
        return \abs($this->upper - $this->lower) / \abs($this->step);
    }

    public function includes($n)
    {
        if ($this->upper >= $this->lower) {
            return $n >= $this->lower && $n <= $this->upper;
        } else {
            return $n <= $this->lower && $n >= $this->upper;
        }
    }

    public function random($seed = null)
    {
        if (isset($seed)) mt_srand($seed);
        return $this->upper >= $this->lower ?
            mt_rand($this->lower, $this->upper) :
            mt_rand($this->upper, $this->lower);
    }

    public function rewind()
    {
        $this->current = $this->lower;
    }

    public function key()
    {
        return $this->current;
    }

    public function valid()
    {
        if ($this->upper >= $this->lower) {
            return $this->current >= $this->lower &&
                $this->current <= $this->upper;
        } else {
            return $this->current <= $this->lower &&
                $this->current >= $this->upper;
        }
    }

    public function next()
    {
        $this->current += $this->step;
        return $this;
    }

    public function current()
    {
        return $this->current;
    }
}

class Cycler implements \IteratorAggregate
{
    protected $elements;
    protected $length;
    protected $idx;

    public function __construct($elements)
    {
        $this->elements = $elements;
        $this->length = count($this->elements);
        $this->idx = 0;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }

    public function next()
    {
        return $this->elements[($this->idx++) % ($this->length)];
    }

    public function random($seed = null)
    {
        if (isset($seed)) mt_srand($seed);
        return $this->elements[mt_rand(0, $this->length - 1)];
    }

    public function count()
    {
        return $this->idx;
    }

    public function cycle()
    {
        return ceil($this->idx / $this->length);
    }
}

function abs($obj = null)
{
    return \abs(intval($obj));
}

function bytes($obj = null, $decimals = 1, $dec = '.', $sep = ',')
{
    $obj = max(0, intval($obj));
    $places = strlen($obj);
    if ($places <= 9 && $places >= 7) {
        $obj = \number_format($obj / 1048576, $decimals, $dec, $sep);
        return "$obj MB";
    } elseif ($places >= 10) {
        $obj = \number_format($obj / 1073741824, $decimals, $dec, $sep);
        return "$obj GB";
    } else {
        $obj = \number_format($obj / 1024, $decimals, $dec, $sep);
        return "$obj KB";
    }
}

function capitalize($obj)
{
    return ucfirst(strval($obj));
}

function cycle($obj = null)
{
    $obj = ($obj instanceof \Traversable) ?
        iterator_to_array($obj) : (array) $obj;
    return new Cycler((array) $obj);
}

function date($obj = null, $format = 'Y-m-d')
{
    return \date($format, $obj ?: time());
}

function dump($obj = null)
{
    echo '<pre>';
    print_r($obj);
    echo '</pre>';
}

function e($obj = null, $force = false)
{
    return escape($obj, $force);
}

function escape($obj = null, $force = false)
{
    return htmlspecialchars(strval($obj), ENT_QUOTES, 'UTF-8', $force);
}

function first($obj = null, $default = null)
{
    if (is_string($obj)) return strlen($obj) ? substr($obj, 0, 1) : $default;
    $obj = ($obj instanceof \Traversable) ?
        iterator_to_array($obj) : (array) $obj;
    $keys = array_keys($obj);
    if (count($keys)) {
        return $obj[$keys[0]];
    }
    return $default;
}

function format($obj, $args)
{
    return call_user_func_array('sprintf', func_get_args());
}

function is_divisible_by($obj = null, $number = null)
{
    if (!isset($number)) return false;
    if (!is_numeric($obj) || !is_numeric($number)) return false;
    if ($number == 0) return false;
    return ($obj % $number == 0);
}

function is_empty($obj = null)
{
    if (is_null($obj)) {
        return true;
    } elseif (is_array($obj)) {
        return empty($obj);
    } elseif (is_string($obj)) {
        return strlen($obj) == 0;
    } elseif ($obj instanceof \Countable) {
        return count($obj) ? false : true;
    } elseif ($obj instanceof \Traversable) {
        return iterator_count($obj);
    } else {
        return false;
    }
}

function is_even($obj = null)
{
    if (is_scalar($obj) || is_null($obj)) {
        $obj = is_numeric($obj) ? intval($obj) : strlen($obj);
    } elseif (is_array($obj)) {
        $obj = count($obj);
    } elseif ($obj instanceof \Traversable) {
        $obj = iterator_count($obj);
    } else {
        return false;
    }
    return \abs($obj % 2) == 0;
}

function is_odd($obj = null)
{
    if (is_scalar($obj) || is_null($obj)) {
        $obj = is_numeric($obj) ? intval($obj) : strlen($obj);
    } elseif (is_array($obj)) {
        $obj = count($obj);
    } elseif ($obj instanceof \Traversable) {
        $obj = iterator_count($obj);
    } else {
        return false;
    }
    return \abs($obj % 2) == 1;
}

function join($obj = null, $glue = '')
{
    $obj = ($obj instanceof \Traversable) ?
        iterator_to_array($obj) : (array) $obj;
    return \join($glue, $obj);
}

function json_encode($obj = null)
{
    return \json_encode($obj);
}

function keys($obj = null)
{
    if (is_array($obj)) {
        return array_keys($obj);
    } elseif ($obj instanceof \Traversable) {
        return array_keys(iterator_to_array($obj));
    }
    return null;
}

function last($obj = null, $default = null)
{
    if (is_string($obj)) return strlen($obj) ? substr($obj, -1) : $default;
    $obj = ($obj instanceof \Traversable) ?
        iterator_to_array($obj) : (array) $obj;
    $keys = array_keys($obj);
    if ($len = count($keys)) {
        return $obj[$keys[$len - 1]];
    }
    return $default;
}

function length($obj = null)
{
    if (is_string($obj)) {
        return strlen($obj);
    } elseif (is_array($obj) || ($obj instanceof \Countable)) {
        return count($obj);
    } elseif ($obj instanceof \Traversable) {
        return iterator_count($obj);
    } else {
        return 1;
    }
}

function lower($obj = null)
{
    return strtolower(strval($obj));
}

function nl2br($obj = null, $is_xhtml = false)
{
    return \nl2br(strval($obj), $is_xhtml);
}

function number_format($obj = null, $decimals = 0, $dec_point = '.',
    $thousands_sep = ',')
{
    return \number_format(strval($obj), $decimals, $dec_point, $thousands_sep);
}

function range($lower = null, $upper = null, $step = 1)
{
    return new RangeIterator(intval($lower), intval($upper), intval($step));
}

function repeat($obj, $times = 2)
{
    return str_repeat(strval($obj), $times);
}

function replace($obj = null, $search = '', $replace = '', $regex = false)
{
    if ($regex) {
        return preg_replace($search, $replace, strval($obj));
    } else {
        return str_replace($search, $replace, strval($obj));
    }
}

function strip_tags($obj = null, $allowableTags = '')
{
    return \strip_tags(strval($obj), $allowableTags);
}

function title($obj = null)
{
    return ucwords(strval($obj));
}

function trim($obj = null, $charlist = " \t\n\r\0\x0B")
{
    return \trim(strval($obj), $charlist);
}

function truncate($obj = null, $length = 255, $preserve_words = false,
    $hellip = '&hellip;')
{
    $obj = strval($obj);

    $truncated = $preserve_words ?
        preg_replace('/\s+?(\S+)?$/', '', substr($obj, 0, $length + 1)) :
        substr($obj, 0, $length);

    if (strlen($obj) > $length) {
        $truncated .= $hellip;
    }
    return $truncated;
}

function unescape($obj = null)
{
    return htmlspecialchars_decode(strval($obj), ENT_QUOTES);
}

function upper($obj = null)
{
    return strtoupper(strval($obj));
}

function url_encode($obj = null)
{
    return urlencode(strval($obj));
}

function word_wrap($obj = null, $width = 75, $break = "\n", $cut = false)
{
    return wordwrap(strval($obj), $width, $break, $cut);
}

