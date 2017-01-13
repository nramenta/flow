<?php

namespace Flow\Expression;

final class NegExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('-');
    }
}

