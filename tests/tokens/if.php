<?php

namespace Flow;

return [
    new Token(Token::BLOCK_BEGIN, '{%', 1, 1),
    new Token(Token::NAME, 'if', 1, 4),
    new Token(Token::CONSTANT, 'true', 1, 7),
    new Token(Token::BLOCK_END, '%}', 1, 12),
    new Token(Token::TEXT, "\ntrue\n", 1, 14),

    new Token(Token::BLOCK_BEGIN, '{%', 3, 1),
    new Token(Token::NAME, 'elseif', 3, 4),
    new Token(Token::NAME, 'foo', 3, 11),
    new Token(Token::BLOCK_END, '%}', 3, 15),
    new Token(Token::TEXT, "\nfoo\n", 3, 17),

    new Token(Token::BLOCK_BEGIN, '{%', 5 ,1),
    new Token(Token::NAME, 'elseif', 5, 4),
    new Token(Token::NAME, 'bar', 5, 11),
    new Token(Token::BLOCK_END, '%}', 5, 15),
    new Token(Token::TEXT, "\nbar\n", 5, 17),

    new Token(Token::BLOCK_BEGIN, '{%', 7, 1),
    new Token(Token::NAME, 'else', 7, 4),
    new Token(Token::BLOCK_END, '%}', 7, 9),
    new Token(Token::TEXT, "\nunknown\n", 7, 11),

    new Token(Token::BLOCK_BEGIN, '{%', 9, 1),
    new Token(Token::NAME, 'endif', 9, 4),
    new Token(Token::BLOCK_END, '%}', 9, 10),
    new Token(Token::TEXT, "\n\n", 9, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 11, 1),
    new Token(Token::STRING, 'this will be printed', 11, 4),
    new Token(Token::NAME, 'if', 11, 27),
    new Token(Token::CONSTANT, 'true', 11, 30),
    new Token(Token::OUTPUT_END, '}}', 11, 35),
    new Token(Token::TEXT, "\n", 11, 37),

    new Token(Token::OUTPUT_BEGIN, '{{', 12, 1),
    new Token(Token::STRING, 'this will never be printed', 12, 4),
    new Token(Token::NAME, 'if', 12, 33),
    new Token(Token::CONSTANT, 'false', 12, 36),
    new Token(Token::OUTPUT_END, '}}', 12, 42),
    new Token(Token::TEXT, "\n", 12, 44),

    new Token(Token::EOF, null, 13, 1),
];

