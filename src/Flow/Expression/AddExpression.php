<?php

namespace Flow\Expression;

final class AddExpression extends BinaryExpression
{
    public function operator() : string
    {
        return '+';
    }
}

