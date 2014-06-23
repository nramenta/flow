<?php

namespace Flow\Expression;

use Flow\Expression;

class NameExpression extends Expression
{
    protected $name;

    public function __construct($name, $line)
    {
        parent::__construct($line);
        $this->name = $name;
    }

    public function raw($compiler, $indent = 0)
    {
        $compiler->raw($this->name, $indent);
    }

    public function repr($compiler, $indent = 0)
    {
        $compiler->repr($this->name, $indent);
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(isset($context[\'' . $this->name . '\']) ? ', $indent);
        $compiler->raw('$context[\'' . $this->name . '\'] : null)');
    }
}

