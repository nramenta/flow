<?php

namespace Flow\Expression;

class NotExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('!');
    }
}

