<?php

namespace Flow\Expression;

final class XorExpression extends BinaryExpression
{
    public function operator()
    {
        return 'xor';
    }
}

