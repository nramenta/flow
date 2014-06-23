<?php

namespace Flow\Expression;

class JoinExpression extends BinaryExpression
{
    public function operator()
    {
        return ".' '.";
    }
}

