<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::STRING, 'Hello World', 1, 4),
    new Token(Token::OUTPUT_END, '}}', 1, 18),
    new Token(Token::TEXT, "\n", 1, 20),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::CONSTANT, 'false', 2, 4),
    new Token(Token::OUTPUT_END, '}}', 2, 10),
    new Token(Token::TEXT, "\n", 2, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::CONSTANT, 'true', 3, 4),
    new Token(Token::OUTPUT_END, '}}', 3, 9),
    new Token(Token::TEXT, "\n", 3, 11),

    new Token(Token::OUTPUT_BEGIN, '{{', 4, 1),
    new Token(Token::CONSTANT, 'null', 4, 4),
    new Token(Token::OUTPUT_END, '}}', 4, 9),
    new Token(Token::TEXT, "\n", 4, 11),

    new Token(Token::OUTPUT_BEGIN, '{{', 5, 1),
    new Token(Token::NUMBER, '2', 5, 4),
    new Token(Token::OUTPUT_END, '}}', 5, 6),
    new Token(Token::TEXT, "\n", 5, 8),

    new Token(Token::OUTPUT_BEGIN, '{{', 6, 1),
    new Token(Token::NUMBER, '3.14', 6, 4),
    new Token(Token::OUTPUT_END, '}}', 6, 9),
    new Token(Token::TEXT, "\n", 6, 11),

    new Token(Token::OUTPUT_BEGIN, '{{', 7, 1),
    new Token(Token::NUMBER, '2000', 7, 4),
    new Token(Token::OUTPUT_END, '}}', 7, 10),
    new Token(Token::TEXT, "\n", 7, 12),

    new Token(Token::OUTPUT_BEGIN, '{{', 8, 1),
    new Token(Token::NUMBER, '2020', 8, 4),
    new Token(Token::OUTPUT_END, '}}', 8, 12),
    new Token(Token::TEXT, "\n", 8, 14),

    new Token(Token::OUTPUT_BEGIN, '{{', 9, 1),
    new Token(Token::NUMBER, '0', 9, 4),
    new Token(Token::OUTPUT_END, '}}', 9, 6),
    new Token(Token::TEXT, "\n", 9, 8),

    new Token(Token::OUTPUT_BEGIN, '{{', 10, 1),
    new Token(Token::NUMBER, '1', 10, 4),
    new Token(Token::OUTPUT_END, '}}', 10, 6),
    new Token(Token::TEXT, "\n", 10, 8),

    new Token(Token::OUTPUT_BEGIN, '{{', 11, 1),
    new Token(Token::NUMBER, '-1', 11, 4),
    new Token(Token::OUTPUT_END, '}}', 11, 7),
    new Token(Token::TEXT, "\n", 11, 9),

    new Token(Token::OUTPUT_BEGIN, '{{', 12, 1),
    new Token(Token::STRING, '<b>bold</b>', 12, 4),
    new Token(Token::OUTPUT_END, '}}', 12, 18),
    new Token(Token::TEXT, "\n", 12, 20),

    new Token(Token::RAW_BEGIN, '{!', 13, 1),
    new Token(Token::STRING, '<i>italic</i>', 13, 4),
    new Token(Token::RAW_END, '!}', 13, 20),
    new Token(Token::TEXT, "\n", 13, 22),

    new Token(Token::EOF, null, 14, 1),
];

