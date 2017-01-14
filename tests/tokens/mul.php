<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::NUMBER, '1', 1, 4),
    new Token(Token::OPERATOR, '*', 1, 6),
    new Token(Token::NUMBER, '2', 1, 8),
    new Token(Token::OUTPUT_END, '}}', 1, 10),
    new Token(Token::TEXT, "\n", 1, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::NUMBER, '2', 2, 4),
    new Token(Token::OPERATOR, '*', 2, 6),
    new Token(Token::NUMBER, '1', 2, 8),
    new Token(Token::OUTPUT_END, '}}', 2, 10),
    new Token(Token::TEXT, "\n", 2, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::NUMBER, '0', 3, 4),
    new Token(Token::OPERATOR, '*', 3, 6),
    new Token(Token::NUMBER, '1', 3, 8),
    new Token(Token::OUTPUT_END, '}}', 3, 10),
    new Token(Token::TEXT, "\n", 3, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 4, 1),
    new Token(Token::NUMBER, '1000', 4, 4),
    new Token(Token::OPERATOR, '*', 4, 10),
    new Token(Token::NUMBER, '2', 4, 12),
    new Token(Token::OUTPUT_END, '}}', 4, 14),
    new Token(Token::TEXT, "\n", 4, 16),

    new Token(Token::OUTPUT_BEGIN, '{{', 5, 1),
    new Token(Token::NUMBER, '-1', 5, 4),
    new Token(Token::OPERATOR, '*', 5, 7),
    new Token(Token::NUMBER, '-1', 5, 9),
    new Token(Token::OUTPUT_END, '}}', 5, 12),
    new Token(Token::TEXT, "\n", 5, 14),

    new Token(Token::EOF, null, 6, 1),
];

