<?php

namespace Flow\Expression;

class NegExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('-');
    }
}

