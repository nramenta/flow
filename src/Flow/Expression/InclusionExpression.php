<?php

namespace Flow\Expression;

class InclusionExpression extends LogicalExpression
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(in_array(', $indent);
        $this->left->compile($compiler);
        $compiler->raw(', (array)');
        $this->right->compile($compiler);
        $compiler->raw('))');
    }
}

