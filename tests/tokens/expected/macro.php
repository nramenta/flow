<?php

namespace Flow;

return [
    new Token(Token::BLOCK_START, '{%', 1, 1),
    new Token(Token::NAME, 'macro', 1, 4),
    new Token(Token::NAME, 'bold', 1, 10),
    new Token(Token::OPERATOR, '(', 1, 14),
    new Token(Token::NAME, 'text', 1, 15),
    new Token(Token::OPERATOR, '=', 1, 19),
    new Token(Token::STRING, 'Hello, World!', 1, 20),
    new Token(Token::OPERATOR, ')', 1, 35),
    new Token(Token::BLOCK_END, '%}', 1, 37),

    new Token(Token::TEXT, "\n<b>", 1, 39),
    new Token(Token::OUTPUT_START, '{{', 2, 4),
    new Token(Token::NAME, 'text', 2, 7),
    new Token(Token::OUTPUT_END, '}}', 2, 12),
    new Token(Token::TEXT, "</b>\n", 2, 14),

    new Token(Token::BLOCK_START, '{%', 3, 1),
    new Token(Token::NAME, 'endmacro', 3, 4),
    new Token(Token::BLOCK_END, '%}', 3, 13),
    new Token(Token::TEXT, "\n\n", 3, 15),

    new Token(Token::OUTPUT_START, '{{', 5, 1),
    new Token(Token::OPERATOR, '@', 5, 4),
    new Token(Token::NAME, 'bold', 5, 5),
    new Token(Token::OUTPUT_END, '}}', 5, 10),
    new Token(Token::TEXT, "\n", 5, 12),

    new Token(Token::OUTPUT_START, '{{', 6, 1),
    new Token(Token::OPERATOR, '@', 6, 4),
    new Token(Token::NAME, 'bold', 6, 5),
    new Token(Token::OPERATOR, '(', 6, 9),
    new Token(Token::STRING, 'Foo', 6, 10),
    new Token(Token::OPERATOR, ')', 6, 15),
    new Token(Token::OUTPUT_END, '}}', 6, 17),
    new Token(Token::TEXT, "\n", 6, 19),

    new Token(Token::OUTPUT_START, '{{', 7, 1),
    new Token(Token::OPERATOR, '@', 7, 4),
    new Token(Token::NAME, 'bold', 7, 5),
    new Token(Token::OPERATOR, '(', 7, 9),
    new Token(Token::NAME, 'text', 7, 10),
    new Token(Token::OPERATOR, '=', 7, 14),
    new Token(Token::STRING, 'Bar', 7, 15),
    new Token(Token::OPERATOR, ')', 7, 20),
    new Token(Token::OUTPUT_END, '}}', 7, 22),
    new Token(Token::TEXT, "\n", 7, 24),

    new Token(Token::EOF, null, 8, 1),
];

