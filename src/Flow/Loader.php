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
            $file = __DIR__ . '/' . implode('/', $class) . '.php';
            if (is_readable($file)) {
                include $file;
            }
        });

        $autoload = true;
    }

    public function __construct($options)
    {
        $this->options = $options + array(
            'prefix'  => self::CLASS_PREFIX,
            'source'  => '',
            'target'  => '',
            'reload'  => false,
            'helpers' => array(),
        );
    }

    public function load($file, $options = array())
    {
        if ($file instanceof Template) {
            return $file;
        }

        $options = $options + $this->options;

        $class  = $options['prefix'] . md5($file);
        $source = $options['source'] . DIRECTORY_SEPARATOR . $file;
        $target = $options['target'] . DIRECTORY_SEPARATOR . $class . '.php';
        $reload = $options['reload'];

        if (!class_exists($class, false)) {
            if (!file_exists($target) ||
                filemtime($target) < filemtime($source) ||
                $reload
            ) {
                $lexer    = new Lexer($source, $file);
                $parser   = new Parser($lexer->tokenize(), $file);
                $compiler = new Compiler($parser->parse());
                $compiler->compile($target);
            }
            include $target;
        }
        return new $class($this, $options['helpers']);
    }
}

