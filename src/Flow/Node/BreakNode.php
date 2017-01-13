<?php

namespace Flow\Node;

use Flow\Node;

final class BreakNode extends Node
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw("break;\n", $indent);
    }
}

