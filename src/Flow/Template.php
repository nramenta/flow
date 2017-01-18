<?php

namespace Flow;

use Flow\Helper;

abstract class Template
{
    protected $loader;
    protected $helpers;
    protected $parent;
    protected $blocks;
    protected $macros;
    protected $imports;
    protected $stack;

    public function __construct($loader, $helpers = [])
    {
        $this->loader  = $loader;
        $this->helpers = $helpers;
        $this->parent  = null;
        $this->blocks  = [];
        $this->macros  = [];
        $this->imports = [];
        $this->stack   = [];
    }

    private function getPath($template)
    {
        if ($template{0} != '/') {
            return dirname(static::NAME) . '/' . $template;
        } else {
            return $template;
        }
    }

    public function loadExtends($template)
    {
        try {
            return $this->loader->load($this->getPath($template));
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'error extending %s (%s) from %s line %d',
                var_export($template, true), $e->getMessage(), static::NAME,
                $this->getLineTrace($e)
            ));
        }
    }

    public function loadInclude($template)
    {
        try {
            return $this->loader->load($this->getPath($template));
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'error including %s (%s) from %s line %d',
                var_export($template, true), $e->getMessage(), static::NAME,
                $this->getLineTrace($e)
            ));
        }
    }

    public function loadImport($template)
    {
        try {
            return $this->loader->load($this->getPath($template))->getMacros();
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'error importing %s (%s) from %s line %d',
                var_export($template, true), $e->getMessage(), static::NAME,
                $this->getLineTrace($e)
            ));
        }
    }

    public function displayBlock($name, $context, $blocks, $macros, $imports)
    {
        $blocks  = $blocks + $this->blocks;
        $macros  = $macros + $this->macros;
        $imports = $imports + $this->imports;
        if (isset($blocks[$name]) && is_callable($blocks[$name])) {
            return call_user_func(
                $blocks[$name], $context, $blocks, $macros, $imports
            );
        }
    }

    public function displayParent($name, $context, $blocks, $macros, $imports)
    {
        $parent = $this;
        while ($parent = $parent->parent) {
            if (isset($parent->blocks[$name]) &&
                is_callable($parent->blocks[$name])) {
                return call_user_func($parent->blocks[$name], $context, $blocks,
                        $macros, $imports);
            }
        }
    }

    public function expandMacro($module, $name, $params, $context, $macros, $imports, $block)
    {
        $macros  = $macros + $this->macros;
        $imports = $imports + $this->imports;
        if (isset($module) && isset($imports[$module])) {
            $macros = $macros + $imports[$module];
        }
        if (isset($macros[$name]) && is_callable($macros[$name])) {
            return call_user_func($macros[$name], $params, $context, $macros, $imports, $block);
        }
    }

    public function pushContext(&$context, $name)
    {
        if (!array_key_exists($name, $this->stack)) {
            $this->stack[$name] = [];
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

    public function helper($name, $args = [])
    {
        $args = func_get_args();
        $name = array_shift($args);

        try {
            $helper = ['\Flow\Helper', $name];
            if (isset($this->helpers[$name]) &&
                is_callable($this->helpers[$name])) {
                return call_user_func_array($this->helpers[$name], $args);
            } elseif (is_callable($helper)) {
                return call_user_func_array($helper, $args);
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

    abstract public function display($context = [], $blocks = [],
        $macros = [], $imports = []);

    public function render($context = [], $blocks = [],
        $macros = [], $imports = [])
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

    public function getBlocks()
    {
        return $this->blocks;
    }

    public function getMacros()
    {
        return $this->macros;
    }

    public function getImports()
    {
        return $this->imports;
    }

    public function getAttr($obj, $attr, $args = [])
    {
        if (is_array($obj)) {
            if (isset($obj[$attr])) {
                if ($obj[$attr] instanceof \Closure) {
                    if (is_array($args)) {
                        array_unshift($args, $obj);
                    } else {
                        $args = [$obj];
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
                $callable = [$obj, $attr];
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
                    $callable = [$obj, $attr];
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
            if (!is_array($obj)) $obj = [];
            $this->setAttr($obj[$attr], $attrs, $value);
        }
    }
}

