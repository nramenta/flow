<?php

namespace Flow;

return [
    new Token(Token::BLOCK_BEGIN, '{%', 1, 1),
    new Token(Token::NAME, 'assign', 1, 4),
    new Token(Token::NAME, 'answer_to_life_and_everything', 1, 11),
    new Token(Token::OPERATOR, '=', 1, 41),
    new Token(Token::NUMBER, '42', 1, 43),
    new Token(Token::BLOCK_END, '%}', 1, 46),
    new Token(Token::TEXT, "\n\n", 1, 48),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::NAME, 'answer_to_life_and_everything', 3, 4),
    new Token(Token::OUTPUT_END, '}}', 3, 34),
    new Token(Token::TEXT, "\n\n", 3, 36),

    new Token(Token::BLOCK_BEGIN, '{%', 5, 1),
    new Token(Token::NAME, 'assign', 5, 4),
    new Token(Token::NAME, 'partial', 5, 11),
    new Token(Token::BLOCK_END, '%}', 5, 19),
    new Token(Token::TEXT, "\nThis is a partial\n", 5, 21),

    new Token(Token::BLOCK_BEGIN, '{%', 7, 1),
    new Token(Token::NAME, 'endassign', 7, 4),
    new Token(Token::BLOCK_END, '%}', 7, 14),
    new Token(Token::TEXT, "\n\n", 7, 16),

    new Token(Token::OUTPUT_BEGIN, '{{', 9, 1),
    new Token(Token::NAME, 'partial', 9, 4),
    new Token(Token::OUTPUT_END, '}}', 9, 12),
    new Token(Token::TEXT, "\n", 9, 14),

    new Token(Token::EOF, null, 10, 1),
];

