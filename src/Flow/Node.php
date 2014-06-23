<?php

namespace Flow;

class Node
{
    protected $line;

    public function __construct($line)
    {
        $this->line = $line;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function addTraceInfo($compiler, $indent)
    {
        return $compiler->addTraceInfo($this, $indent);
    }

    public function compile($compiler, $indent = 0)
    {
    }
}

