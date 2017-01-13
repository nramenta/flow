<?php

namespace Flow\Expression;

final class PosExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('+');
    }
}

