<?php

namespace Flow;

use PHPUnit_Framework_TestCase;

class LexerTest extends PHPUnit_Framework_TestCase
{
    public function test_tokenize_returns_TokenStream()
    {
        $source = 'foobar';

        $lexer = new Lexer($source);

        $this->assertTrue($lexer->tokenize() instanceof TokenStream);
    }
}

