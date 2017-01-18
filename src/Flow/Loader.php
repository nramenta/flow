<?php

namespace Flow;

final class Loader
{
    const CLASS_PREFIX = '__FlowTemplate_';

    const RECOMPILE_NEVER = -1;
    const RECOMPILE_NORMAL = 0;
    const RECOMPILE_ALWAYS = 1;

    private $mode;
    private $source;
    private $target;
    private $helpers;
    private $cache;

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

    public function __construct($mode, Adapter $source, Adapter $target, array $helpers = [])
    {
        $this->mode    = $mode;
        $this->source  = $source;
        $this->target  = $target;
        $this->helpers = $helpers;
        $this->cache   = [];
    }

    private function getClassName(string $path) : string
    {
        return self::CLASS_PREFIX . md5($path);
    }

    private function getCanonicalPath(string $template) : string
    {
        $path = preg_replace('#/{2,}#', '/', strtr($template, '\\', '/'));
        $parts = [];

        foreach (explode('/', $path) as $i => $part) {
            if ($part === '..') {
                if (empty($parts)) {
                    throw new \RuntimeException(sprintf(
                        '%s resolves to a path outside source',
                        $template
                    ));
                } else {
                    array_pop($parts);
                }
            } elseif ($part !== '.') {
                $parts[] = $part;
            }
        }
        return trim(implode('/', $parts), '/');
    }

    public function load(string $template) : Template
    {
        $path = $this->getCanonicalPath($template);

        $class = $this->getClassName($path);

        if (!isset($this->cache[$class])) {

            if (!$this->source->isReadable($path)) {
                throw new \RuntimeException(sprintf(
                    '%s is not a valid readable template',
                    $template
                ));
            }

            $file = $class . '.php';

            switch ($this->mode) {
            case self::RECOMPILE_ALWAYS:
                $compile = true;
                break;
            case self::RECOMPILE_NEVER:
                $compile = !$this->target->isReadable($file);
                break;
            case self::RECOMPILE_NORMAL:
            default:
                $compile = !$this->target->isReadable($file) ||
                    $this->target->lastModified($file) < $this->source->lastModified($path);
                break;
            }

            if ($compile) {
                try {
                    $lexer    = new Lexer($this->source->getContents($path));
                    $parser   = new Parser($lexer->tokenize());
                    $compiler = new Compiler($parser->parse($path, $class));
                    $compiled = $compiler->compile();
                    $this->target->putContents($file, $compiled);
                } catch (SyntaxError $e) {
                    throw $e->setMessage($path . ': ' . $e->getMessage());
                }
            }

            require_once $this->target->getStreamUrl($file);

            $this->cache[$class] = new $class($this, $this->helpers);
        }

        return $this->cache[$class];
    }
}

