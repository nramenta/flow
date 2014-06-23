<?php

namespace Flow;

class SyntaxError extends \Exception
{
    protected $token;

    public function __construct($message, $token)
    {
        $this->token = $token;

        $line = $token->getLine();
        $char = $token->getChar();
        parent::__construct("$message in line $line char $char");
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }
}

