<?php

namespace Flow\Expression;

class XorExpression extends BinaryExpression
{
    public function operator()
    {
        return 'xor';
    }
}

