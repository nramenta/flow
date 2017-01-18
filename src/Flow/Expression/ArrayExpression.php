<?php

namespace Flow\Expression;

use Flow\Expression;

final class ArrayExpression extends Expression
{
    private $elements;

    public function __construct($elements, $line)
    {
        parent::__construct($line);
        $this->elements = $elements;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('[', $indent);
        foreach ($this->elements as $node) {
            if (is_array($node)) {
                $node[0]->compile($compiler);
                $compiler->raw(' => ');
                $node[1]->compile($compiler);
            } else {
                $node->compile($compiler);
            }
            $compiler->raw(',');
        }
        $compiler->raw(']');
    }
}

