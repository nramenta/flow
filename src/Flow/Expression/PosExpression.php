<?php

namespace Flow\Expression;

final class PosExpression extends UnaryExpression
{
    public function operator() : string
    {
        return '+';
    }
}

