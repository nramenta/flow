<?php

namespace Flow\Expression;

class ConcatExpression extends BinaryExpression
{
    public function operator()
    {
        return '.';
    }
}

