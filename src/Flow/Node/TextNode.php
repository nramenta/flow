<?php

namespace Flow\Node;

use Flow\Node;

final class TextNode extends Node
{
    private $data;

    public function __construct($data, $line)
    {
        parent::__construct($line);
        $this->data = $data;
    }

    public function compile($compiler, $indent = 0)
    {
        if (!strlen($this->data)) return;
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw('echo ', $indent);
        $compiler->repr($this->data);
        $compiler->raw(";\n");
    }
}

