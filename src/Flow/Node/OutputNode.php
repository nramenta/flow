<?php

namespace Flow\Node;

use Flow\Node;

class OutputNode extends Node
{
    protected $expr;

    public function __construct($expr, $line)
    {
        parent::__construct($line);
        $this->expr = $expr;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw('echo ', $indent);
        $this->expr->compile($compiler);
        $compiler->raw(";\n");
    }
}

