<?php

namespace Flow\Expression;

final class SubExpression extends BinaryExpression
{
    public function operator() : string
    {
        return '-';
    }
}

