<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::NUMBER, '2', 1, 4),
    new Token(Token::OPERATOR, '-', 1, 6),
    new Token(Token::NUMBER, '1', 1, 8),
    new Token(Token::OUTPUT_END, '}}', 1, 10),
    new Token(Token::TEXT, "\n", 1, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::NUMBER, '3', 2, 4),
    new Token(Token::OPERATOR, '-', 2, 6),
    new Token(Token::NUMBER, '3', 2, 8),
    new Token(Token::OUTPUT_END, '}}', 2, 10),
    new Token(Token::TEXT, "\n", 2, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::NUMBER, '1', 3, 4),
    new Token(Token::OPERATOR, '-', 3, 6),
    new Token(Token::NUMBER, '2', 3, 8),
    new Token(Token::OUTPUT_END, '}}', 3, 10),
    new Token(Token::TEXT, "\n", 3, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 4, 1),
    new Token(Token::NUMBER, '1', 4, 4),
    new Token(Token::OPERATOR, '-', 4, 6),
    new Token(Token::NUMBER, '3', 4, 8),
    new Token(Token::OUTPUT_END, '}}', 4, 10),
    new Token(Token::TEXT, "\n", 4, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 5, 1),
    new Token(Token::NUMBER, '0', 5, 4),
    new Token(Token::OPERATOR, '-', 5, 6),
    new Token(Token::NUMBER, '-1', 5, 8),
    new Token(Token::OUTPUT_END, '}}', 5, 11),
    new Token(Token::TEXT, "\n", 5, 13),

    new Token(Token::OUTPUT_BEGIN, '{{', 6, 1),
    new Token(Token::NUMBER, '-1', 6, 4),
    new Token(Token::OPERATOR, '-', 6, 7),
    new Token(Token::NUMBER, '-1', 6, 9),
    new Token(Token::OUTPUT_END, '}}', 6, 12),
    new Token(Token::TEXT, "\n", 6, 14),

    new Token(Token::OUTPUT_BEGIN, '{{', 7, 1),
    new Token(Token::NUMBER, '0', 7, 4),
    new Token(Token::OPERATOR, '-', 7, 6),
    new Token(Token::NUMBER, '0', 7, 8),
    new Token(Token::OUTPUT_END, '}}', 7, 10),
    new Token(Token::TEXT, "\n", 7, 12),

    new Token(Token::EOF, null, 8, 1),
];

