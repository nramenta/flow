<?php

namespace Flow\Expression;

final class NotExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('!');
    }
}

