<?php

namespace Flow\Expression;

final class JoinExpression extends BinaryExpression
{
    public function operator() : string
    {
        return ".' '.";
    }
}

