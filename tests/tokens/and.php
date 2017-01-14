<?php

namespace Flow;

return [
    new Token(Token::OUTPUT_BEGIN, '{{', 1, 1),
    new Token(Token::CONSTANT, 'true', 1, 4),
    new Token(Token::OPERATOR, 'and', 1, 9),
    new Token(Token::CONSTANT, 'true', 1, 13),
    new Token(Token::OUTPUT_END, '}}', 1, 18),
    new Token(Token::TEXT, "\n", 1, 20),

    new Token(Token::OUTPUT_BEGIN, '{{', 2, 1),
    new Token(Token::CONSTANT, 'true', 2, 4),
    new Token(Token::OPERATOR, 'and', 2, 9),
    new Token(Token::CONSTANT, 'false', 2, 13),
    new Token(Token::OUTPUT_END, '}}', 2, 19),
    new Token(Token::TEXT, "\n", 2, 21),

    new Token(Token::OUTPUT_BEGIN, '{{', 3, 1),
    new Token(Token::CONSTANT, 'false', 3, 4),
    new Token(Token::OPERATOR, 'and', 3, 10),
    new Token(TOKEN::CONSTANT, 'true', 3, 14),
    new Token(Token::OUTPUT_END, '}}', 3, 19),
    new Token(Token::TEXT, "\n", 3, 21),

    new Token(Token::OUTPUT_BEGIN, '{{', 4, 1),
    new Token(Token::CONSTANT, 'false', 4, 4),
    new Token(Token::OPERATOR, 'and', 4, 10),
    new Token(Token::CONSTANT, 'false', 4, 14),
    new Token(Token::OUTPUT_END, '}}', 4, 20),
    new Token(Token::TEXT, "\n", 4, 22),

    new Token(Token::EOF, null, 5, 1),
];

