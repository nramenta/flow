<?php

namespace Flow;

final class Loader
{
    const CLASS_PREFIX = '__FlowTemplate_';

    const RECOMPILE_NEVER = -1;
    const RECOMPILE_NORMAL = 0;
    const RECOMPILE_ALWAYS = 1;

    private $options;
    private $paths;
    private $cache;
    private $source;
    private $helpers;

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

    public function __construct(array $options, Adapter $source, array $helpers = [])
    {
        if (!isset($options['source'])) {
            throw new \RuntimeException('missing source directory');
        }

        if (!isset($options['target'])) {
            throw new \RuntimeException('missing target directory');
        }

        $options += array(
            'mode'    => self::RECOMPILE_NORMAL,
        );

        if (!($target = realpath($options['target'])) || !is_dir($target)) {
            throw new \RuntimeException(sprintf(
                'target directory %s not found',
                $options['target']
            ));
        }

        $this->options = array(
            'source'  => $options['source'],
            'target'  => $target,
            'mode'    => $options['mode'],
        );

        $this->helpers = $helpers;
        $this->paths   = array();
        $this->cache   = array();
        $this->source  = $source;
    }

    private function getClassName($path)
    {
        return self::CLASS_PREFIX . md5($path);
    }

    private function normalizePath($path)
    {
        $path = preg_replace('#/{2,}#', '/', strtr($path, '\\', '/'));
        $parts = array();
        foreach (explode('/', $path) as $i => $part) {
            if ($part === '..') {
                if (!empty($parts)) array_pop($parts);
            } elseif ($part !== '.') {
                $parts[] = $part;
            }
        }
        return $parts;
    }

    private function resolvePath($template, $from = '')
    {
        $source = implode('/', $this->normalizePath($this->options['source']));

        $parts = $this->normalizePath(
            $source . '/' . dirname($from) . '/' . $template
        );

        foreach ($this->normalizePath($source) as $i => $part) {
            if ($part !== $parts[$i]) {
                throw new \RuntimeException(sprintf(
                    '%s is outside the source directory',
                    $template
                ));
            }
        }

        $path = trim(substr(implode('/', $parts), strlen($source)), '/');

        return $path;
    }

    public function load(string $template, $from = '')
    {
        if (isset($this->paths[$template . $from])) {
            $path = $this->paths[$template . $from];
        } else {
            $path = $this->resolvePath($template, $from);
            $this->paths[$template . $from] = $path;
        }

        $class = $this->getClassName($path);

        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }

        if (!class_exists($class, false)) {

            if (!$this->source->isReadable($path)) {
                throw new \RuntimeException(sprintf(
                    '%s is not a valid readable template',
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
                    filemtime($target) < $this->source->lastModified($path);
                break;
            }

            if ($compile) {
                try {
                    $lexer    = new Lexer($this->source->getContents($path));
                    $parser   = new Parser($lexer->tokenize());
                    $compiler = new Compiler($parser->parse());
                    $compiler->compile($path, $target);
                } catch (SyntaxError $e) {
                    throw $e->setMessage($path . ': ' . $e->getMessage());
                }
            }
            require_once $target;
        }

        $this->cache[$class] = new $class($this, $this->helpers);

        return $this->cache[$class];
    }
}

