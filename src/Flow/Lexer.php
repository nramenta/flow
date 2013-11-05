<?php

namespace Flow;

class Lexer
{
    protected $source;
    protected $line;
    protected $char;
    protected $cursor;
    protected $position;
    protected $queue;
    protected $end;
    protected $trim;

    const BLOCK_START      = '{%';
    const BLOCK_START_TRIM = '{%-';
    const BLOCK_END        = '%}';
    const BLOCK_END_TRIM   = '-%}';

    const COMMENT_START      = '{#';
    const COMMENT_START_TRIM = '{#-';
    const COMMENT_END        = '#}';
    const COMMENT_END_TRIM   = '-#}';

    const OUTPUT_START      = '{{';
    const OUTPUT_START_TRIM = '{{-';
    const OUTPUT_END        = '}}';
    const OUTPUT_END_TRIM   = '-}}';

    const POSITION_TEXT  = 0;
    const POSITION_BLOCK = 1;
    const POSITION_OUTPUT   = 2;

    const REGEX_CONSTANT = '/true\b | false\b | null\b/Ax';
    const REGEX_NAME     = '/[a-zA-Z_][a-zA-Z0-9_]*/A';
    const REGEX_NUMBER   = '/[0-9][0-9_]*(?:\.[0-9][0-9_]*)?/A';
    const REGEX_STRING   = '/(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|
        \'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')/Axsmu';
    const REGEX_OPERATOR = '/and\b|xor\b|or\b|not\b|in\b|
        =>|<>|<=?|>=?|[!=]==|[!=]?=|\.\.|[\[\]().,%*\/+|?:\-@~]/Ax';

    public function __construct($source)
    {
        $this->source   = preg_replace("/(\r\n|\r|\n)/", "\n", $source);
        $this->line     = 1;
        $this->char     = 1;
        $this->cursor   = 0;
        $this->position = self::POSITION_TEXT;
        $this->queue    = array();
        $this->end      = strlen($this->source);
        $this->trim     = false;
    }

    public function tokenize($stream = true)
    {
        do {
            $tokens[] = $token = $this->next();
        } while ($token->getType() !== Token::EOF);

        return $stream ? new TokenStream($tokens) : $tokens;
    }

    protected function next()
    {
        if (!empty($this->queue)) {
            return array_shift($this->queue);
        }

        if ($this->cursor >= $this->end) {
            return new Token(Token::EOF, null, $this->line, $this->char);
        }

        switch ($this->position) {

        case self::POSITION_TEXT:
            $this->queue = $this->lexText();
            break;

        case self::POSITION_BLOCK:
            $this->queue = $this->lexBlock();
            break;

        case self::POSITION_OUTPUT:
            $this->queue = $this->lexOutput();
            break;
        }

        return $this->next();
    }

    public function adjustLineChar($string)
    {
        if (($nl = substr_count($string, "\n")) > 0) {
            $this->line += $nl;
            $this->char = strlen($string) - strrpos($string, "\n");
        } else {
            $this->char += strlen($string);
        }
    }

    protected function lexText()
    {
        $match = null;
        $tokens = array();

        // all text
        if (!preg_match('/(.*?)(' .
            preg_quote(self::COMMENT_START_TRIM) .'|' .
            preg_quote(self::COMMENT_START) . '|' .
            preg_quote(self::OUTPUT_START_TRIM) . '|' .
            preg_quote(self::OUTPUT_START) . '|' .
            preg_quote(self::BLOCK_START_TRIM) . '|' .
            preg_quote(self::BLOCK_START) . ')/As', $this->source, $match,
                null, $this->cursor)
        ) {
            $text = substr($this->source, $this->cursor);
            if ($this->trim) {
                $text = preg_replace("/^[ \t]*\n?/", '', $text);
                $this->trim = false;
            }
            $tokens[] = new Token(Token::TEXT, $text, $this->line, $this->char);
            $this->adjustLineChar($text);
            $this->cursor = $this->end;
            return $tokens;
        }

        $this->cursor += strlen($match[0]);

        // text first
        $text  = $match[1];
        $token = $match[2];

        if (strlen($text)) {
            if ($this->trim) {
                $text = preg_replace("/^[ \t]*\n?/", '', $text);
                $this->trim = false;
            }
            if ($token == self::COMMENT_START_TRIM ||
                $token == self::BLOCK_START_TRIM ||
                $token == self::OUTPUT_START_TRIM) {
                $text = rtrim($text, " \t");
            }
            $tokens[] = new Token(Token::TEXT, $text, $this->line, $this->char);
        }

        $this->adjustLineChar($match[1]);

        switch ($token) {

        case self::COMMENT_START_TRIM:
        case self::COMMENT_START:
            if (preg_match('/.*?(' .
                preg_quote(self::COMMENT_END_TRIM) . '|' .
                preg_quote(self::COMMENT_END) . ')/As',
                    $this->source, $match, null, $this->cursor)
            ) {
                if ($match[1] == self::COMMENT_END_TRIM) {
                    $this->trim = true;
                }
                $this->cursor += strlen($match[0]);
                $this->adjustLineChar($match[0]);
            }
            break;

        case self::BLOCK_START_TRIM:
        case self::BLOCK_START:
            if (preg_match('/(\s*raw\s*)(' .
                preg_quote(self::BLOCK_END_TRIM) . '|' .
                preg_quote(self::BLOCK_END) . ')(.*?)(' .
                preg_quote(self::BLOCK_START_TRIM) . '|' .
                preg_quote(self::BLOCK_START) . ')(\s*endraw\s*)(' .
                preg_quote(self::BLOCK_END_TRIM) . '|' .
                preg_quote(self::BLOCK_END) . ')/As',
                    $this->source, $match, null, $this->cursor)
            ) {
                $raw = $match[3];
                if ($match[2] == self::BLOCK_END_TRIM) {
                    $raw = preg_replace("/^[ \t]*\n?/", '', $raw);
                }
                if ($match[4] == self::BLOCK_START_TRIM) {
                    $raw = rtrim($raw, " \t");
                }
                if ($match[6] == self::BLOCK_END_TRIM) {
                    $this->trim = true;
                }
                $before = $token . $match[1] . $match[2];
                $after  = $match[3] . $match[4] . $match[5] . $match[6];
                $this->cursor += strlen($match[0]);
                $this->adjustLineChar($before);
                $tokens[] = new Token(
                    Token::TEXT, $raw, $this->line, $this->char
                );
                $this->adjustLineChar($after);
                $this->position = self::POSITION_TEXT;
            } else {
                $tokens[] = new Token(
                    Token::BLOCK_START, $token, $this->line, $this->char
                );
                $this->adjustLineChar($token);
                $this->position = self::POSITION_BLOCK;
            }
            break;

        case self::OUTPUT_START_TRIM:
        case self::OUTPUT_START:
            $tokens[] = new Token(
                Token::OUTPUT_START, $token, $this->line, $this->char
            );
            $this->adjustLineChar($token);
            $this->position = self::POSITION_OUTPUT;
            break;

        }

        return $tokens;
    }

    protected function lexBlock()
    {
        $tokens = array();
        $match = null;

        if (preg_match('/(\s*)(' .
            preg_quote(self::BLOCK_END_TRIM) . '|' .
            preg_quote(self::BLOCK_END) . ')/A',
                $this->source, $match, null, $this->cursor)
        ) {
            if ($match[2] == self::BLOCK_END_TRIM) {
                $this->trim = true;
            }
            $this->cursor += strlen($match[0]);
            $this->adjustLineChar($match[1]);
            $tokens[] = new Token(
                Token::BLOCK_END, $match[2], $this->line, $this->char
            );
            $this->adjustLineChar($match[2]);
            $this->position = self::POSITION_TEXT;

            return $tokens;
        }
        return $this->lexExpression();
    }

    protected function lexOutput()
    {
        $tokens = array();
        $match = null;

        if (preg_match('/(\s*)(' .
            preg_quote(self::OUTPUT_END_TRIM) . '|' .
            preg_quote(self::OUTPUT_END) . ')/A',
                $this->source, $match, null, $this->cursor)
        ) {
            if ($match[2] == self::OUTPUT_END_TRIM) {
                $this->trim = true;
            }
            $this->cursor += strlen($match[0]);
            $this->adjustLineChar($match[1]);
            $tokens[] = new Token(
                Token::OUTPUT_END, $match[2], $this->line, $this->char
            );
            $this->adjustLineChar($match[2]);
            $this->position = self::POSITION_TEXT;

            return $tokens;
        }
        return $this->lexExpression();
    }

    protected function lexExpression()
    {
        $tokens = array();
        $match = null;

        // eat whitespace
        if (preg_match('/\s+/A', $this->source, $match, null, $this->cursor)) {
            $this->cursor += strlen($match[0]);
            $this->adjustLineChar($match[0]);
        }

        if (preg_match(self::REGEX_NUMBER, $this->source, $match, null,
            $this->cursor)
        ) {
            $this->cursor += strlen($match[0]);
            $number = str_replace('_', '', $match[0]);
            $tokens[] = new Token(
                Token::NUMBER, $number, $this->line, $this->char
            );
            $this->adjustLineChar($match[0]);

        } elseif (preg_match(self::REGEX_OPERATOR, $this->source, $match, null,
            $this->cursor)
        ) {
            $this->cursor += strlen($match[0]);
            $operator = $match[0];
            $tokens[] = new Token(
                Token::OPERATOR, $operator, $this->line, $this->char
            );
            $this->adjustLineChar($match[0]);

        } elseif (preg_match(self::REGEX_CONSTANT, $this->source, $match, null,
            $this->cursor)
        ) {
            $this->cursor += strlen($match[0]);
            $constant = $match[0];
            $tokens[] = new Token(
                Token::CONSTANT, $constant, $this->line, $this->char
            );
            $this->adjustLineChar($match[0]);

        } elseif (preg_match(self::REGEX_NAME, $this->source, $match, null,
            $this->cursor)
        ) {
            $this->cursor += strlen($match[0]);
            $name = $match[0];
            $tokens[] = new Token(Token::NAME, $name, $this->line, $this->char);
            $this->adjustLineChar($match[0]);

        } elseif (preg_match(self::REGEX_STRING, $this->source, $match, null,
            $this->cursor)
        ) {
            $this->cursor += strlen($match[0]);
            $string = stripcslashes(substr($match[0], 1, strlen($match[0])-2));
            $tokens[] = new Token(
                Token::STRING, $string, $this->line, $this->char
            );
            $this->adjustLineChar($match[0]);

        } elseif ($this->position == self::POSITION_BLOCK &&
            preg_match('/(.+?)\s*(' .
            preg_quote(self::BLOCK_END_TRIM) . '|' .
            preg_quote(self::BLOCK_END) . ')/As',
                $this->source, $match, null, $this->cursor)
        ) {
            // a catch-all text token
            $this->cursor += strlen($match[1]);
            $text = $match[1];
            $tokens[] = new Token(Token::TEXT, $text, $this->line, $this->char);
            $this->adjustLineChar($match[1]);

        } elseif ($this->position == self::POSITION_OUTPUT &&
            preg_match('/(.+?)\s*(' .
            preg_quote(self::OUTPUT_END_TRIM) . '|' .
            preg_quote(self::OUTPUT_END) . ')/As',
                $this->source, $match, null, $this->cursor)
        ) {
            $this->cursor += strlen($match[1]);
            $text = $match[1];
            $tokens[] = new Token(Token::TEXT, $text, $this->line, $this->char);
            $this->adjustLineChar($match[1]);

        } else {
            $text = substr($this->source, $this->cursor);
            $this->cursor += $this->end;
            $tokens[] = new Token(Token::TEXT, $text, $this->line, $this->char);
            $this->adjustLineChar($text);
        }

        return $tokens;
    }
}

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

class TokenStream
{
    protected $tokens;
    protected $currentToken;
    protected $queue;
    protected $cursor;
    protected $eos;

    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
        $this->currentToken = null;
        $this->queue = array();
        $this->cursor = 0;
        $this->eos = false;
        $this->next();
    }

    public function next($queue = true)
    {
        if ($this->eos) {
            return $this->currentToken;
        }

        $token = $this->tokens[$this->cursor++];

        $old = $this->currentToken;

        $this->currentToken = $token;

        $this->eos = ($token->getType() === Token::EOF);

        return $old;
    }

    public function look($t = 1)
    {
        $t--;
        $length = count($this->tokens);
        if ($this->cursor + $t > $length) $t = 0;
        if ($this->cursor + $t < 0) $t = -$this->cursor;
        return $this->tokens[$this->cursor + $t];
    }

    public function skip($times = 1)
    {
        for ($i = 0; $i < $times; $i++) {
            $this->next();
        }
        return $this;
    }

    public function expect($primary, $secondary = null)
    {
        $token = $this->getCurrentToken();
        if (is_null($secondary) && !is_int($primary)) {
            $secondary = $primary;
            $primary = Token::NAME;
        }
        if (!$token->test($primary, $secondary)) {
            if (is_null($secondary)) {
                $expecting = Token::getTypeError($primary);
            } elseif (is_array($secondary)) {
                $expecting = '"' . implode('" or "', $secondary) . '"';
            } else {
                $expecting = '"' . $secondary . '"';
            }
            if ($token->getType() === Token::EOF) {
                throw new SyntaxError('unexpected end of file', $token);
            } else {
                throw new SyntaxError(
                    sprintf(
                        'unexpected "%s", expecting %s',
                        str_replace("\n", '\n', $token->getValue()), $expecting
                    ),
                    $token
                );
            }
        }
        $this->next();
        return $token;
    }

    public function expectTokens($tokens)
    {
        foreach ($tokens as $token) {
            $this->expect($token->getType(), $token->getValue());
        }
        return $this;
    }

    public function test($primary, $secondary = null)
    {
        return $this->getCurrentToken()->test($primary, $secondary);
    }

    public function consume($primary, $secondary = null)
    {
        if ($this->test($primary, $secondary)) {
            $this->expect($primary, $secondary);
            return true;
        } else {
            return false;
        }
    }

    public function isEOS()
    {
        return $this->eos;
    }

    public function getCurrentToken()
    {
        return $this->currentToken;
    }

    public function getTokens()
    {
        return $this->tokens;
    }
}

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

