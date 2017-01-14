<?php

namespace Flow;

return [
    new Token(Token::BLOCK_BEGIN, '{%', 1, 1),
    new Token(Token::NAME, 'for', 1, 4),
    new Token(Token::NAME, 'i', 1, 8),
    new Token(Token::OPERATOR, 'in', 1, 10),
    new Token(Token::OPERATOR, '[', 1, 13),
    new Token(Token::NUMBER, '1', 1, 14),
    new Token(Token::OPERATOR, ',', 1, 15),
    new Token(Token::NUMBER, '2', 1, 16),
    new Token(Token::OPERATOR, ',', 1, 17),
    new Token(Token::NUMBER, '3', 1, 18),
    new Token(Token::OPERATOR, ']', 1, 19),
    new Token(Token::BLOCK_END, '%}', 1, 21),
    new Token(Token::TEXT, "\n  ", 1, 23),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 3),
    new Token(Token::NAME, 'i', 2, 6),
    new Token(Token::OUTPUT_END, '}}', 2, 8),
    new Token(Token::TEXT, "\n", 2, 10),

    new Token(Token::BLOCK_BEGIN, '{%', 3, 1),
    new Token(Token::NAME, 'endfor', 3, 4),
    new Token(Token::BLOCK_END, '%}', 3, 11),
    new Token(Token::TEXT, "\n\n", 3, 13),

    new Token(Token::BLOCK_BEGIN, '{%', 5, 1),
    new Token(Token::NAME, 'for', 5, 4),
    new Token(Token::NAME, 'i', 5, 8),
    new Token(Token::OPERATOR, 'in', 5, 10),
    new Token(Token::STRING, 'hello', 5, 13),
    new Token(Token::BLOCK_END, '%}', 5, 21),
    new Token(Token::TEXT, "\n  ", 5, 23),

    new Token(Token::OUTPUT_BEGIN, '{{', 6, 3),
    new Token(Token::NAME, 'i', 6, 6),
    new Token(Token::OUTPUT_END, '}}', 6, 8),
    new Token(Token::TEXT, "\n", 6, 10),

    new Token(Token::BLOCK_BEGIN, '{%', 7, 1),
    new Token(Token::NAME, 'else', 7, 4),
    new Token(Token::BLOCK_END, '%}', 7, 9),
    new Token(Token::TEXT, "\nstrings are not iterable\n", 7, 11),

    new Token(Token::BLOCK_BEGIN, '{%', 9, 1),
    new Token(Token::NAME, 'endfor', 9, 4),
    new Token(Token::BLOCK_END, '%}', 9, 11),
    new Token(Token::TEXT, "\n", 9, 13),

    new Token(Token::EOF, null, 10, 1),
];

