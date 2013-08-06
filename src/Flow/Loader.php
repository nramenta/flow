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
        if (!isset($options['source'])) {
            throw new \RuntimeException('missing source directory');
        }

        if (!isset($options['target'])) {
            throw new \RuntimeException('missing target directory');
        }

        $options += array(
            'mode'    => self::RECOMPILE_NORMAL,
            'mkdir'   => 0777,
            'helpers' => array(),
        );

        if (!isset($options['adapter'])) {
            $options['adapter'] = new FileAdapter($options['source']);
        }

        if (!($target = realpath($options['target'])) || !is_dir($target)) {
            if ($options['mkdir'] === false) {
                throw new \RuntimeException(sprintf(
                    'target directory %s not found',
                    $options['target']
                ));
            }
            if (!mkdir($options['target'], $options['mkdir'], true)) {
                throw new \RuntimeException(sprintf(
                    'unable to create target directory %s',
                    $options['target']
                ));
            }
        }

        $this->options = array(
            'source'  => $options['source'],
            'target'  => $target,
            'mode'    => $options['mode'],
            'adapter' => $options['adapter'],
            'helpers' => $options['helpers'],
        );

        $this->paths = array();
        $this->cache = array();
    }

    public function normalizePath($path)
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

    public function resolvePath($template, $from = '')
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

    public function compile($template, $mode = null)
    {
        $source  = $this->options['source'];
        $adapter = $this->options['adapter'];

        $path = $this->resolvePath($template);

        $class = self::CLASS_PREFIX . md5($path);

        if (!$adapter->isReadable($path)) {
            throw new \RuntimeException(sprintf(
                '%s is not a valid readable template',
                $template
            ));
        }

        $target = $this->options['target'] . '/' . $class . '.php';

        if (!isset($mode)) {
            $mode = $this->options['mode'];
        }

        switch ($mode) {
        case self::RECOMPILE_ALWAYS:
            $compile = true;
            break;
        case self::RECOMPILE_NEVER:
            $compile = !file_exists($target);
            break;
        case self::RECOMPILE_NORMAL:
        default:
            $compile = !file_exists($target) ||
                filemtime($target) < $adapter->lastModified($path);
            break;
        }

        if ($compile) {
            $lexer    = new Lexer($path, $adapter->getContents($path));
            $parser   = new Parser($lexer->tokenize());
            $compiler = new Compiler($parser->parse());
            $compiler->compile($target);
        }

        return $this;
    }

    public function load($template, $from = '')
    {
        if ($template instanceof Template) {
            return $template;
        }

        $source  = $this->options['source'];
        $adapter = $this->options['adapter'];

        if (isset($this->paths[$template . $from])) {
            $path = $this->paths[$template . $from];
        } else {
            $path = $this->resolvePath($template, $from);
            $this->paths[$template . $from] = $path;
        }

        $class = self::CLASS_PREFIX . md5($path);

        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }

        if (!class_exists($class, false)) {

            if (!$adapter->isReadable($path)) {
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
                    filemtime($target) < $adapter->lastModified($path);
                break;
            }

            if ($compile) {
                $lexer    = new Lexer($path, $adapter->getContents($path));
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
        $source  = $this->options['source'];
        $adapter = $this->options['adapter'];

        $path = $this->resolvePath($template);

        $class = self::CLASS_PREFIX . md5($path);

        if (!$adapter->isReadable($path)) {
            throw new \RuntimeException(sprintf(
                '%s is not a valid readable template',
                $template
            ));
        }

        try {
            $lexer    = new Lexer($path, $adapter->getContents($path));
            $parser   = new Parser($lexer->tokenize());
            $compiler = new Compiler($parser->parse());
        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }
        return true;
    }
}

interface Adapter
{
    public function isReadable($path);
    public function lastModified($path);
    public function getContents($path);
}

class FileAdapter implements Adapter
{
    protected $source;

    public function __construct($source)
    {
        if (!($this->source = realpath($source)) || !is_dir($this->source)) {
            throw new \RuntimeException(sprintf(
                'source directory %s not found',
                $source
            ));
        }
    }

    public function isReadable($path)
    {
        return is_readable($this->source. '/' . $path);
    }

    public function lastModified($path)
    {
        return filemtime($this->source. '/' . $path);
    }

    public function getContents($path)
    {
        return file_get_contents($this->source . '/' . $path);
    }
}

