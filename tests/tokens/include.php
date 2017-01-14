<?php

namespace Flow;

return [
    new Token(Token::BLOCK_BEGIN, '{%', 1, 1),
    new Token(Token::NAME, 'include', 1, 4),
    new Token(Token::STRING, "includes/partial.html", 1, 12),
    new Token(Token::BLOCK_END, '%}', 1, 36),
    new Token(Token::TEXT, "\n", 1, 38),

    new Token(Token::EOF, null, 2, 1),
];

