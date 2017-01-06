<?php

namespace Flow;

abstract class Node
{
    private $line;

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

    abstract public function compile($compiler, $indent = 0);
}

