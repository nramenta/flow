<?php

namespace Flow\Node;

use Flow\Node;

class ParentNode extends Node
{
    protected $name;

    public function __construct($name, $line)
    {
        parent::__construct($line);
        $this->name = $name;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw(
            '$this->displayParent(\'' . $this->name .
            '\', $context, $blocks, $macros, $imports);' . "\n", $indent
        );
    }
}

