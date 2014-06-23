<?php

namespace Flow\Expression;

use Flow\Expression;

class UnaryExpression extends Expression
{
    protected $node;

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

