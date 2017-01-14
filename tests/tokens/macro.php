<?php

namespace Flow;

return [
    new Token(Token::BLOCK_BEGIN, '{%', 1, 1),
    new Token(Token::NAME, 'macro', 1, 4),
    new Token(Token::NAME, 'bold', 1, 10),
    new Token(Token::OPERATOR, '(', 1, 14),
    new Token(Token::NAME, 'text', 1, 15),
    new Token(Token::OPERATOR, '=', 1, 19),
    new Token(Token::STRING, 'Hello, World!', 1, 20),
    new Token(Token::OPERATOR, ')', 1, 35),
    new Token(Token::BLOCK_END, '%}', 1, 37),

    new Token(Token::TEXT, "\n<b>", 1, 39),
    new Token(Token::RAW_BEGIN, '{!', 2, 4),
    new Token(Token::NAME, 'text', 2, 7),
    new Token(Token::RAW_END, '!}', 2, 12),
    new Token(Token::TEXT, "</b>\n<i>", 2, 14),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 4),
    new Token(Token::NAME, 'text', 3, 7),
    new Token(Token::OUTPUT_END, '}}', 3, 12),
    new Token(Token::TEXT, "</i>\n", 3, 14),

    new Token(Token::BLOCK_BEGIN, '{%', 4, 1),
    new Token(Token::NAME, 'endmacro', 4, 4),
    new Token(Token::BLOCK_END, '%}', 4, 13),
    new Token(Token::TEXT, "\n\n", 4, 15),

    new Token(Token::BLOCK_BEGIN, '{%', 6, 1),
    new Token(Token::NAME, 'call', 6, 4),
    new Token(Token::NAME, 'bold', 6, 9),
    new Token(Token::BLOCK_END, '%}', 6, 14),
    new Token(Token::TEXT, "\n", 6, 16),

    new Token(Token::BLOCK_BEGIN, '{%', 7, 1),
    new Token(Token::NAME, 'call', 7, 4),
    new Token(Token::NAME, 'bold', 7, 9),
    new Token(Token::OPERATOR, '(', 7, 13),
    new Token(Token::STRING, 'Foo', 7, 14),
    new Token(Token::OPERATOR, ')', 7, 19),
    new Token(Token::BLOCK_END, '%}', 7, 21),
    new Token(Token::TEXT, "\n", 7, 23),

    new Token(Token::BLOCK_BEGIN, '{%', 8, 1),
    new Token(Token::NAME, 'call', 8, 4),
    new Token(Token::NAME, 'bold', 8, 9),
    new Token(Token::OPERATOR, '(', 8, 13),
    new Token(Token::NAME, 'text', 8, 14),
    new Token(Token::OPERATOR, '=', 8, 18),
    new Token(Token::STRING, '<i>Bar</i>', 8, 19),
    new Token(Token::OPERATOR, ')', 8, 31),
    new Token(Token::BLOCK_END, '%}', 8, 33),
    new Token(Token::TEXT, "\n", 8, 35),

    new Token(Token::EOF, null, 9, 1),
];

