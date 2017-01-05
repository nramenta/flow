<?php

namespace Flow;

return [
    new Token(Token::BLOCK_START, '{%', 1, 1),
    new Token(Token::NAME, 'set', 1, 4),
    new Token(Token::NAME, 'answer_to_life_and_everything', 1, 8),
    new Token(Token::OPERATOR, '=', 1, 38),
    new Token(Token::NUMBER, '42', 1, 40),
    new Token(Token::BLOCK_END, '%}', 1, 43),
    new Token(Token::TEXT, "\n\n", 1, 45),

    new Token(Token::OUTPUT_START, '{{', 3, 1),
    new Token(Token::NAME, 'answer_to_life_and_everything', 3, 4),
    new Token(Token::OUTPUT_END, '}}', 3, 34),
    new Token(Token::TEXT, "\n\n", 3, 36),

    new Token(Token::BLOCK_START, '{%', 5, 1),
    new Token(Token::NAME, 'set', 5, 4),
    new Token(Token::NAME, 'partial', 5, 8),
    new Token(Token::BLOCK_END, '%}', 5, 16),
    new Token(Token::TEXT, "\nThis is a partial\n", 5, 18),

    new Token(Token::BLOCK_START, '{%', 7, 1),
    new Token(Token::NAME, 'endset', 7, 4),
    new Token(Token::BLOCK_END, '%}', 7, 11),
    new Token(Token::TEXT, "\n\n", 7, 13),

    new Token(Token::OUTPUT_START, '{{', 9, 1),
    new Token(Token::NAME, 'partial', 9, 4),
    new Token(Token::OUTPUT_END, '}}', 9, 12),
    new Token(Token::TEXT, "\n", 9, 14),

    new Token(Token::EOF, null, 10, 1),
];

