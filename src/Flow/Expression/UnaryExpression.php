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

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(', $indent);
        $this->operator($compiler);
        $compiler->raw('(');
        $this->node->compile($compiler);
        $compiler->raw('))');
    }
}

