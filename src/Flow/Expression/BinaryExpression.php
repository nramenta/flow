<?php

namespace Flow\Expression;

use Flow\Expression;

abstract class BinaryExpression extends Expression
{
    private $left;
    private $right;

    public function __construct($left, $right, $line)
    {
        parent::__construct($line);
        $this->left = $left;
        $this->right = $right;
    }

    public function getLeftOperand()
    {
        return $this->left;
    }

    public function getRightOperand()
    {
        return $this->right;
    }

    abstract public function operator() : string;

    public function compile($compiler, $indent = 0)
    {
        $op = $this->operator($compiler);
        $compiler->raw('(', $indent);
        $this->left->compile($compiler);
        $compiler->raw(' ' . $op . ' ');
        $this->right->compile($compiler);
        $compiler->raw(')');
    }
}

