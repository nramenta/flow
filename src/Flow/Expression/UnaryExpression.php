<?php

namespace Flow\Expression;

use Flow\Expression;

abstract class UnaryExpression extends Expression
{
    private $node;

    public function __construct($node, $line)
    {
        parent::__construct($line);
        $this->node = $node;
    }

    abstract public function operator() : string;

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(', $indent);
        $compiler->raw($this->operator());
        $compiler->raw('(');
        $this->node->compile($compiler);
        $compiler->raw('))');
    }
}

