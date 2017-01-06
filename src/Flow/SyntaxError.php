<?php

namespace Flow;

final class SyntaxError extends \Exception
{
    private $token;

    public function __construct(string $message, Token $token)
    {
        $this->token = $token;

        $line = $token->getLine();
        $char = $token->getChar();
        parent::__construct("$message in line $line char $char");
    }

    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }
}

