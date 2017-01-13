<?php

namespace Flow\Expression;

use Flow\Expression;

final class StringExpression extends Expression
{
    private $value;

    public function __construct($value, $line)
    {
        parent::__construct($line);
        $this->value = $value;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->repr($this->value);
    }
}

