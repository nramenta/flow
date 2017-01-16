<?php

namespace Flow\Expression;

final class NotExpression extends UnaryExpression
{
    public function operator() : string
    {
        return '!';
    }
}

