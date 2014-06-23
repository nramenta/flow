<?php

namespace Flow;

class Token
{
    protected $type;
    protected $value;
    protected $line;
    protected $char;

    const EOF          = -1;
    const TEXT         = 0;
    const BLOCK_START  = 1;
    const OUTPUT_START = 2;
    const BLOCK_END    = 3;
    const OUTPUT_END   = 4;
    const NAME         = 5;
    const NUMBER       = 6;
    const STRING       = 7;
    const OPERATOR     = 8;
    const CONSTANT     = 9;

    public function __construct($type, $value, $line, $char)
    {
        $this->type  = $type;
        $this->value = $value;
        $this->line  = $line;
        $this->char  = $char;
    }

    public static function getTypeAsString($type, $canonical = false)
    {
        if (is_string($type)) {
            return $canonical ? (__CLASS__ . '::' . $type) : $type;
        }

        switch ($type) {
        case self::EOF:
            $name = 'EOF';
            break;
        case self::TEXT:
            $name = 'TEXT';
            break;
        case self::BLOCK_START:
            $name = 'BLOCK_START';
            break;
        case self::OUTPUT_START:
            $name = 'OUTPUT_START';
            break;
        case self::BLOCK_END:
            $name = 'BLOCK_END';
            break;
        case self::OUTPUT_END:
            $name = 'OUTPUT_END';
            break;
        case self::NAME:
            $name = 'NAME';
            break;
        case self::NUMBER:
            $name = 'NUMBER';
            break;
        case self::STRING:
            $name = 'STRING';
            break;
        case self::OPERATOR:
            $name = 'OPERATOR';
            break;
        case self::CONSTANT:
            $name = 'CONSTANT';
            break;
        }
        return $canonical ? (__CLASS__ . '::' . $name) : $name;
    }

    public static function getTypeError($type)
    {
        switch ($type) {
        case self::EOF:
            $name = 'end of file';
            break;
        case self::TEXT:
            $name = 'text type';
            break;
        case self::BLOCK_START:
            $name = 'block start (either "' . Lexer::BLOCK_START . '" or "' .
                Lexer::BLOCK_START_TRIM . '")';
            break;
        case self::OUTPUT_START:
            $name = 'block start (either "' . Lexer::OUTPUT_START . '" or "' .
                Lexer::OUTPUT_START_TRIM . '")';
            break;
        case self::BLOCK_END:
            $name = 'block end (either "' . Lexer::BLOCK_END . '" or "' .
                Lexer::BLOCK_END_TRIM . '")';
            break;
        case self::OUTPUT_END:
            $name = 'block end (either "' . Lexer::OUTPUT_END . '" or "' .
                Lexer::OUTPUT_END_TRIM . '")';
            break;
        case self::NAME:
            $name = 'name type';
            break;
        case self::NUMBER:
            $name = 'number type';
            break;
        case self::STRING:
            $name = 'string type';
            break;
        case self::OPERATOR:
            $name = 'operator type';
            break;
        case self::CONSTANT:
            $name = 'constant type (true, false, or null)';
            break;
        }
        return $name;
    }

    public function test($type, $values = null)
    {
        if (is_null($values) && !is_int($type)) {
            $values = $type;
            $type = self::NAME;
        }

        return ($this->type === $type) && (
            is_null($values) ||
            (is_array($values) && in_array($this->value, $values)) ||
            $this->value == $values
        );
    }

    public function getType($asString = false, $canonical = false)
    {
        if ($asString) {
            return self::getTypeAsString($this->type, $canonical);
        }
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function getChar()
    {
        return $this->char;
    }

    public function __toString()
    {
        return $this->getValue();
    }
}

