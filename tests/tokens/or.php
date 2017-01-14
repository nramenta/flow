<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::CONSTANT, 'true', 1, 4),
    new Token(Token::OPERATOR, 'or', 1, 9),
    new Token(Token::CONSTANT, 'true', 1, 12),
    new Token(Token::OUTPUT_END, '}}', 1, 17),
    new Token(Token::TEXT, "\n", 1, 19),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::CONSTANT, 'true', 2, 4),
    new Token(Token::OPERATOR, 'or', 2, 9),
    new Token(Token::CONSTANT, 'false', 2, 12),
    new Token(Token::OUTPUT_END, '}}', 2, 18),
    new Token(Token::TEXT, "\n", 2, 20),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::CONSTANT, 'false', 3, 4),
    new Token(Token::OPERATOR, 'or', 3, 10),
    new Token(Token::CONSTANT, 'true', 3, 13),
    new Token(Token::OUTPUT_END, '}}', 3, 18),
    new Token(Token::TEXT, "\n", 3, 20),

    new Token(Token::OUTPUT_BEGIN, '{{', 4, 1),
    new Token(Token::CONSTANT, 'false', 4, 4),
    new Token(Token::OPERATOR, 'or', 4, 10),
    new Token(Token::CONSTANT, 'false', 4, 13),
    new Token(Token::OUTPUT_END, '}}', 4, 19),
    new Token(Token::TEXT, "\n", 4, 21),

    new Token(Token::EOF, null, 5, 1),
];

