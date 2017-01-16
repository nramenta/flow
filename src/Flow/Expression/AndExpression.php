<?php

namespace Flow\Expression;

final class AndExpression extends LogicalExpression
{
    public function operator() : string
    {
        return '';
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(!($a = ', $indent);
        $this->getLeftOperand()->compile($compiler);
        $compiler->raw(') ? ($a) : (');
        $this->getRightOperand()->compile($compiler);
        $compiler->raw('))');
    }
}

