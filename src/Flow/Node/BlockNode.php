<?php

namespace Flow\Node;

use Flow\Node;

class BlockNode extends Node
{
    protected $name;
    protected $body;

    public function __construct($name, $body, $line)
    {
        parent::__construct($line);
        $this->name = $name;
        $this->body = $body;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw("\n");
        $compiler->addTraceInfo($this, $indent, false);
        $compiler->raw(
            'public function block_' . $this->name .
            '($context, $blocks = array(), $macros = array(),' .
            ' $imports = array())' . "\n", $indent
        );
        $compiler->raw("{\n", $indent);
        $this->body->compile($compiler, $indent + 1);
        $compiler->raw("}\n", $indent);
    }
}

