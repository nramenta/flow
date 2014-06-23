<?php

namespace Flow\Expression;

class ModExpression extends BinaryExpression
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('fmod(', $indent);
        $this->left->compile($compiler);
        $compiler->raw(', ');
        $this->right->compile($compiler);
        $compiler->raw(')');
    }
}

