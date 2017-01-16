<?php

namespace Flow\Expression;

final class NegExpression extends UnaryExpression
{
    public function operator() : string
    {
        return '-';
    }
}

