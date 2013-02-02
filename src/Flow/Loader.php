<?php

namespace Flow;

class Loader
{
    const CLASS_PREFIX = '__FlowTemplate_';

    const RECOMPILE_NEVER = -1;
    const RECOMPILE_NORMAL = 0;
    const RECOMPILE_ALWAYS = 1;

    protected $options;
    protected $paths;
    protected $cache;

    public static function autoload()
    {
        static $autoload = false;

        if ($autoload) return;

        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(function($class) {
            $class = explode('\\', $class);
            array_shift($class);
            $path = __DIR__ . '/' . implode('/', $class) . '.php';
            if (is_readable($path)) {
                include $path;
            }
        });

        $autoload = true;
    }

    public function __construct($options)
    {
        $options += array(
            'source'  => '',
            'target'  => '',
            'mode'    => self::RECOMPILE_NORMAL,
            'helpers' => array(),
        );

        if (!($source = realpath($options['source']))) {
            throw new \RuntimeException(sprintf(
                'source directory %s not found',
                $options['source']
            ));
        }

        if (!($target = realpath($options['target']))) {
            throw new \RuntimeException(sprintf(
                'target directory %s not found',
                $options['target']
            ));
        }

        $this->options = array(
            'source'  => $source,
            'target'  => $target,
            'mode'    => $options['mode'],
            'helpers' => $options['helpers'],
        );

        $this->paths = array();
        $this->cache = array();
    }

    public function resolvePath($template, $from = null)
    {
        $dirname = isset($from) ? trim(dirname($from), './') : '';

        $path = $this->options['source'] . '/';
        if (substr($template, 0, 1) === '/') {
            $path .= trim($template, '/');
        } else {
            $path .= (!empty($dirname) ? $dirname . '/' : '') . $template;
        }

        $path = preg_replace('#/{2,}#', '/', strtr($path, '\\', '/'));

        $up = 0;
        $parts = array();
        foreach (explode('/', $path) as $part) {
            if ($part === '..') {
                if (!empty($parts)) array_pop($parts);
            } elseif ($part !== '.') {
                $parts[] = $part;
            }
        }

        return implode('/', $parts);
    }

    public function load($template, $from = null)
    {
        if ($template instanceof Template) {
            return $template;
        }

        if (isset($this->paths[$template . $from])) {
            $source = $this->paths[$template . $from];
        } else {
            $source = $this->resolvePath($template, $from);
            $this->paths[$template . $from] = $source;
        }

        $name  = substr($source, strlen($this->options['source']) + 1);
        $class = self::CLASS_PREFIX . md5($name);

        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }

        if (!class_exists($class, false)) {

            // $source refers to file outside source directory
            if (strpos(dirname($source), $this->options['source']) !== 0) {
                throw new \RuntimeException(sprintf(
                    'the path %s is outside the source directory',
                    $template
                ));
            }

            // $source is not a readable file
            if (!is_readable($source)) {
                throw new \RuntimeException(sprintf(
                    '%s is not a readable file',
                    $template
                ));
            }

            $target = $this->options['target'] . '/' . $class . '.php';

            switch ($this->options['mode']) {
            case self::RECOMPILE_ALWAYS:
                $compile = true;
                break;
            case self::RECOMPILE_NEVER:
                $compile = !file_exists($target);
                break;
            case self::RECOMPILE_NORMAL:
            default:
                $compile = !file_exists($target) ||
                    filemtime($target) < filemtime($source);
                break;
            }

            if ($compile) {
                $lexer    = new Lexer($name, file_get_contents($source));
                $parser   = new Parser($lexer->tokenize());
                $compiler = new Compiler($parser->parse());
                $compiler->compile($target);
            }
            require_once $target;
        }

        $this->cache[$class] = new $class($this, $this->options['helpers']);

        return $this->cache[$class];
    }

    public function isValid($template, &$error = null)
    {
        $source = $this->resolvePath($template);

        $name  = substr($source, strlen($this->options['source']) + 1);
        $class = self::CLASS_PREFIX . md5($name);

        // $source refers to file outside source directory
        if (strpos(dirname($source), $this->options['source']) !== 0) {
            throw new \RuntimeException(sprintf(
                'the path %s is outside the source directory',
                $template
            ));
        }

        // $source is not a readable file
        if (!is_readable($source)) {
            throw new \RuntimeException(sprintf(
                '%s is not a readable file',
                $template
            ));
        }

        try {
            $lexer    = new Lexer($name, file_get_contents($source));
            $parser   = new Parser($lexer->tokenize());
            $compiler = new Compiler($parser->parse());
        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }
        return true;
    }
}

