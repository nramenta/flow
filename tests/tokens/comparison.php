<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::NUMBER, '1', 1, 4),
    new Token(Token::OPERATOR, '>', 1, 6),
    new Token(Token::NUMBER, '2', 1, 8),
    new Token(Token::OUTPUT_END, '}}', 1, 10),
    new Token(Token::TEXT, "\n", 1, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::NUMBER, '1', 2, 4),
    new Token(Token::OPERATOR, '<', 2, 6),
    new Token(Token::NUMBER, '2', 2, 8),
    new Token(Token::OUTPUT_END, '}}', 2, 10),
    new Token(Token::TEXT, "\n", 2, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::NUMBER, '1', 3, 4),
    new Token(Token::OPERATOR, '>', 3, 6),
    new Token(Token::NUMBER, '2', 3, 8),
    new Token(Token::OPERATOR, '>', 3, 10),
    new Token(Token::NUMBER, '3', 3, 12),
    new Token(Token::OUTPUT_END, '}}', 3, 14),
    new Token(Token::TEXT, "\n", 3, 16),

    new Token(Token::OUTPUT_BEGIN, '{{', 4, 1),
    new Token(Token::NUMBER, '1', 4, 4),
    new Token(Token::OPERATOR, '<', 4, 6),
    new Token(Token::NUMBER, '2', 4, 8),
    new Token(Token::OPERATOR, '<', 4, 10),
    new Token(Token::NUMBER, '3', 4, 12),
    new Token(Token::OUTPUT_END, '}}', 4, 14),
    new Token(Token::TEXT, "\n\n", 4, 16),

    new Token(Token::OUTPUT_BEGIN, '{{', 6, 1),
    new Token(Token::NUMBER, '1', 6, 4),
    new Token(Token::OPERATOR, '>=', 6, 6),
    new Token(Token::NUMBER, '2', 6, 9),
    new Token(Token::OUTPUT_END, '}}', 6, 11),
    new Token(Token::TEXT, "\n", 6, 13),

    new Token(Token::OUTPUT_BEGIN, '{{', 7, 1),
    new Token(Token::NUMBER, '2', 7, 4),
    new Token(Token::OPERATOR, '>=', 7, 6),
    new Token(Token::NUMBER, '2', 7, 9),
    new Token(Token::OUTPUT_END, '}}', 7, 11),
    new Token(Token::TEXT, "\n", 7, 13),

    new Token(Token::OUTPUT_BEGIN, '{{', 8, 1),
    new Token(Token::NUMBER, '1', 8, 4),
    new Token(Token::OPERATOR, '<=', 8, 6),
    new Token(Token::NUMBER, '2', 8, 9),
    new Token(Token::OUTPUT_END, '}}', 8, 11),
    new Token(Token::TEXT, "\n", 8, 13),

    new Token(Token::OUTPUT_BEGIN, '{{', 9, 1),
    new Token(Token::NUMBER, '2', 9, 4),
    new Token(Token::OPERATOR, '<=', 9, 6),
    new Token(Token::NUMBER, '2', 9, 9),
    new Token(Token::OUTPUT_END, '}}', 9, 11),
    new Token(Token::TEXT, "\n\n", 9, 13),

    new Token(Token::OUTPUT_BEGIN, '{{', 11, 1),
    new Token(Token::NUMBER, '1', 11, 4),
    new Token(Token::OPERATOR, '>=', 11, 6),
    new Token(Token::NUMBER, '2', 11, 9),
    new Token(Token::OPERATOR, '>=', 11, 11),
    new Token(Token::NUMBER, '3', 11, 14),
    new Token(Token::OUTPUT_END, '}}', 11, 16),
    new Token(Token::TEXT, "\n", 11, 18),

    new Token(Token::OUTPUT_BEGIN, '{{', 12, 1),
    new Token(Token::NUMBER, '1', 12, 4),
    new Token(Token::OPERATOR, '<=', 12, 6),
    new Token(Token::NUMBER, '2', 12, 9),
    new Token(Token::OPERATOR, '<=', 12, 11),
    new Token(Token::NUMBER, '3', 12, 14),
    new Token(Token::OUTPUT_END, '}}', 12, 16),
    new Token(Token::TEXT, "\n", 12, 18),

    new Token(Token::EOF, null, 13, 1),
];

