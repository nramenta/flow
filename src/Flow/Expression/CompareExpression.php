<?php

namespace Flow\Expression;

use Flow\Expression;

final class CompareExpression extends Expression
{
    private $expr;
    private $ops;

    public function __construct($expr, $ops, $line)
    {
        parent::__construct($line);
        $this->expr = $expr;
        $this->ops = $ops;
    }

    public function compile($compiler, $indent = 0)
    {
        $this->expr->compile($compiler);
        $i = 0;
        foreach ($this->ops as $op) {
            if ($i) {
                $compiler->raw(' && ($tmp' . $i);
            }
            list($op, $node) = $op;
            $compiler->raw(' ' . ($op == '=' ? '==' : $op) . ' ');
            $compiler->raw('($tmp' . ++$i . ' = ');
            $node->compile($compiler);
            $compiler->raw(')');
        }
        if ($i > 1) {
            $compiler->raw(str_repeat(')', $i - 1));
        }
    }
}

