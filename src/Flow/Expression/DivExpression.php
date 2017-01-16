<?php

namespace Flow\Expression;

final class DivExpression extends BinaryExpression
{
    public function operator() : string
    {
        return '/';
    }
}

