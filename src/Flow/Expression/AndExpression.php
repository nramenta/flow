<?php

namespace Flow\Expression;

class AndExpression extends LogicalExpression
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(!($a = ', $indent);
        $this->left->compile($compiler);
        $compiler->raw(') ? ($a) : (');
        $this->right->compile($compiler);
        $compiler->raw('))');
    }
}

