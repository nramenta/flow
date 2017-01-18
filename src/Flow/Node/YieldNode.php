<?php

namespace Flow\Node;

use Flow\Node;

final class YieldNode extends Node
{
    private $args;

    public function __construct($args, $line)
    {
        parent::__construct($line);
        $this->args = $args;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw('call_user_func($block, [', $indent);

        foreach ($this->args as $key => $val) {
            $compiler->raw("'$key' => ");
            $val->compile($compiler);
            $compiler->raw(',');
        }

        $compiler->raw('] + $context);' . "\n");
    }
}

