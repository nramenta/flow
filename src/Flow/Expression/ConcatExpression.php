<?php

namespace Flow\Expression;

final class ConcatExpression extends BinaryExpression
{
    public function operator() : string
    {
        return '.';
    }
}

