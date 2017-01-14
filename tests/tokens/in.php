<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::NUMBER, '1', 1, 4),
    new Token(Token::OPERATOR, 'in', 1, 6),
    new Token(Token::OPERATOR, '[', 1, 9),
    new Token(Token::NUMBER, '1', 1, 10),
    new Token(Token::OPERATOR, ',', 1, 11),
    new Token(Token::NUMBER, '2', 1, 13),
    new Token(Token::OPERATOR, ',', 1, 14),
    new Token(Token::NUMBER, '3', 1, 16),
    new Token(Token::OPERATOR, ']', 1, 17),
    new Token(Token::OUTPUT_END, '}}', 1, 19),
    new Token(Token::TEXT, "\n", 1, 21),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::NUMBER, '0', 2, 4),
    new Token(Token::OPERATOR, 'not', 2, 6),
    new Token(Token::OPERATOR, 'in', 2, 10),
    new Token(Token::OPERATOR, '[', 2, 13),
    new Token(Token::NUMBER, '1', 2, 14),
    new Token(Token::OPERATOR, ',', 2, 15),
    new Token(Token::NUMBER, '2', 2, 17),
    new Token(Token::OPERATOR, ',', 2, 18),
    new Token(Token::NUMBER, '3', 2, 20),
    new Token(Token::OPERATOR, ']', 2, 21),
    new Token(Token::OUTPUT_END, '}}', 2, 23),
    new Token(Token::TEXT, "\n", 2, 25),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::NUMBER, '1', 3, 4),
    new Token(Token::OPERATOR, 'not', 3, 6),
    new Token(Token::OPERATOR, 'in', 3, 10),
    new Token(Token::OPERATOR, '[', 3, 13),
    new Token(Token::NUMBER, '3', 3, 14),
    new Token(Token::OPERATOR, ',', 3, 15),
    new Token(Token::NUMBER, '2', 3, 17),
    new Token(Token::OPERATOR, ',', 3, 18),
    new Token(Token::NUMBER, '1', 3, 20),
    new Token(Token::OPERATOR, ']', 3, 21),
    new Token(Token::OUTPUT_END, '}}', 3, 23),
    new Token(Token::TEXT, "\n", 3, 25),

    new Token(Token::EOF, null, 4, 1),
];

