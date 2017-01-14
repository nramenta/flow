<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::CONSTANT, 'true', 1, 4),
    new Token(Token::OPERATOR, '?', 1, 9),
    new Token(Token::STRING, 'yes', 1, 11),
    new Token(Token::OPERATOR, ':', 1, 17),
    new Token(Token::STRING, 'no', 1, 19),
    new Token(Token::OUTPUT_END, '}}', 1, 24),
    new Token(Token::TEXT, "\n", 1, 26),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::CONSTANT, 'false', 2, 4),
    new Token(Token::OPERATOR, '?', 2, 10),
    new Token(Token::STRING, 'yes', 2, 12),
    new Token(Token::OPERATOR, ':', 2, 18),
    new Token(Token::STRING, 'no', 2, 20),
    new Token(Token::OUTPUT_END, '}}', 2, 25),
    new Token(Token::TEXT, "\n", 2, 27),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::CONSTANT, 'false', 3, 4),
    new Token(Token::OPERATOR, '?', 3, 10),
    new Token(Token::OPERATOR, '(', 3, 12),
    new Token(Token::CONSTANT, 'false', 3, 13),
    new Token(Token::OPERATOR, '?', 3, 19),
    new Token(Token::STRING, 'yes', 3, 21),
    new Token(Token::OPERATOR, ':', 3, 27),
    new Token(Token::STRING, 'no', 3, 29),
    new Token(Token::OPERATOR, ')', 3, 33),
    new Token(Token::OPERATOR, ':', 3, 35),
    new Token(Token::STRING, 'yes', 3, 37),
    new Token(Token::OUTPUT_END, '}}', 3, 43),
    new Token(Token::TEXT, "\n", 3, 45),

    new Token(Token::EOF, null, 4, 1),
];

