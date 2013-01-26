<?php

namespace Flow;

class Loader
{
    const CLASS_PREFIX = '__Template_';

    protected $options;

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
        $this->options = $options + array(
            'source'  => '',
            'target'  => '',
            'reload'  => false,
            'helpers' => array(),
        );
        $this->options['source'] = rtrim($this->options['source'], '/');
        $this->options['target'] = rtrim($this->options['target'], '/');
    }

    public function load($template, $from = null, $line = null)
    {
        if ($template instanceof Template) {
            return $template;
        }

        $options = $this->options;

        $path = $options['source'] . '/' .
            (substr($template, 0, 1) !== '/' && isset($from) ?
                dirname($from) . '/' : null) .
            $template;

        // source is nowhere to be found
        if (!($source = realpath($path))) {
            throw new \RuntimeException(
                isset($from) ?
                sprintf(
                    'error loading %s in %s line %d',
                    $template, $from, $line
                ) : sprintf('error loading %s', $template)
            );
        }

        // source refers to file outside source directory
        if (strpos(dirname($source), $options['source']) !== 0) {
            throw new \RuntimeException(
                isset($from) ?
                sprintf(
                    'error loading %s in %s line %d',
                    $template, $from, $line
                ) : sprintf('error loading %s', $template)
            );
        }

        $name   = substr($source, strlen($options['source']) + 1);
        $class  = self::CLASS_PREFIX . md5($name);
        $target = $options['target'] . '/' . $class . '.php';
        $reload = $options['reload'];

        if (!class_exists($class, false)) {
            if (!file_exists($target) ||
                filemtime($target) < filemtime($source) ||
                $reload
            ) {
                if (!is_readable($source)) {
                    throw new \RuntimeException(
                        isset($from) ?
                        sprintf(
                            'error reading %s in %s line %d',
                            $template, $from, $line
                        ) : sprintf('error reading %s', $template)
                    );
                }

                $lexer    = new Lexer($name, file_get_contents($source));
                $parser   = new Parser($lexer->tokenize());
                $compiler = new Compiler($parser->parse());
                $compiler->compile($target);
            }
            include $target;
        }
        return new $class($this, $options['helpers']);
    }
}

