<?php

namespace Flow;

final class NodeList extends Node
{
    private $nodes;

    public function __construct($nodes, $line)
    {
        parent::__construct($line);
        $this->nodes = $nodes;
    }

    public function compile($compiler, $indent = 0)
    {
        foreach ($this->nodes as $node) {
            $node->compile($compiler, $indent);
        }
    }
}

