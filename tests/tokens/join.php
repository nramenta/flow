<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::STRING, 'foo', 1, 4),
    new Token(Token::OPERATOR, '..', 1, 10),
    new Token(Token::STRING, 'bar', 1, 13),
    new Token(Token::OUTPUT_END, '}}', 1, 19),
    new Token(Token::TEXT, "\n", 1, 21),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::STRING, 'hello ', 2, 4),
    new Token(Token::OPERATOR, '..', 2, 13),
    new Token(Token::STRING, ' world', 2, 16),
    new Token(Token::OUTPUT_END, '}}', 2, 25),
    new Token(Token::TEXT, "\n", 2, 27),

    new Token(Token::EOF, null, 3, 1),
];

