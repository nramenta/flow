<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::NUMBER, '1', 1, 4),
    new Token(Token::OPERATOR, '+', 1, 6),
    new Token(Token::NUMBER, '2', 1, 8),
    new Token(Token::OUTPUT_END, '}}', 1, 10),
    new Token(Token::TEXT, "\n", 1, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::NUMBER, '-1', 2, 4),
    new Token(Token::OPERATOR, '+', 2, 7),
    new Token(Token::NUMBER, '2', 2, 9),
    new Token(Token::OUTPUT_END, '}}', 2, 11),
    new Token(Token::TEXT, "\n", 2, 13),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::NUMBER, '2', 3, 4),
    new Token(Token::OPERATOR, '+', 3, 6),
    new Token(Token::NUMBER, '-2', 3, 8),
    new Token(Token::OUTPUT_END, "}}", 3, 11),
    new Token(Token::TEXT, "\n", 3, 13),

    new Token(Token::OUTPUT_BEGIN, '{{', 4, 1),
    new Token(Token::NUMBER, '1000', 4, 4),
    new Token(Token::OPERATOR, '+', 4, 10),
    new Token(Token::NUMBER, '1', 4, 12),
    new Token(Token::OUTPUT_END, '}}', 4, 14),
    new Token(Token::TEXT, "\n", 4, 16),

    new Token(Token::EOF, null, 5, 1),
];

