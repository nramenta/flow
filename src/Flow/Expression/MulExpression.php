<?php

namespace Flow\Expression;

final class MulExpression extends BinaryExpression
{
    public function operator() : string
    {
        return '*';
    }
}

