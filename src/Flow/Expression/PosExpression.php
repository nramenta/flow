<?php

namespace Flow\Expression;

class PosExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('+');
    }
}

