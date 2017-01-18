<?php

namespace Flow;

final class Lexer
{
    private $source;
    private $line;
    private $char;
    private $cursor;
    private $position;
    private $queue;
    private $end;
    private $trim;

    const BLOCK_BEGIN      = '{%';
    const BLOCK_BEGIN_TRIM = '{%-';
    const BLOCK_END        = '%}';
    const BLOCK_END_TRIM   = '-%}';

    const COMMENT_BEGIN      = '{#';
    const COMMENT_BEGIN_TRIM = '{#-';
    const COMMENT_END        = '#}';
    const COMMENT_END_TRIM   = '-#}';

    const OUTPUT_BEGIN      = '{{';
    const OUTPUT_BEGIN_TRIM = '{{-';
    const OUTPUT_END        = '}}';
    const OUTPUT_END_TRIM   = '-}}';

    const RAW_BEGIN      = '{!';
    const RAW_BEGIN_TRIM = '{!-';
    const RAW_END        = '!}';
    const RAW_END_TRIM   = '-!}';

    const POSITION_TEXT   = 0;
    const POSITION_BLOCK  = 1;
    const POSITION_OUTPUT = 2;
    const POSITION_RAW    = 3;

    const REGEX_CONSTANT = '/true\b | false\b | null\b/Ax';
    const REGEX_NAME     = '/[a-zA-Z_][a-zA-Z0-9_]*/A';
    const REGEX_NUMBER   = '/[\-]?[0-9][0-9_]*(?:\.[0-9][0-9_]*)?/A';
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
        $this->queue    = [];
        $this->end      = strlen($this->source);
        $this->trim     = false;
    }

    public function tokenize() : TokenStream
    {
        $tokens = [];

        do {
            $tokens[] = $token = $this->next();
        } while ($token->getType() !== Token::EOF);

        return new TokenStream($tokens);
    }

    private function next() : Token
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

        case self::POSITION_RAW:
            $this->queue = $this->lexRaw();
            break;
        }

        return $this->next();
    }

    private function adjustLineChar($string)
    {
        if (($nl = substr_count($string, "\n")) > 0) {
            $this->line += $nl;
            $this->char = strlen($string) - strrpos($string, "\n");
        } else {
            $this->char += strlen($string);
        }
    }

    private function lexText() : array
    {
        $match = null;
        $tokens = [];

        // all text
        if (!preg_match('/(.*?)(' .
            preg_quote(self::COMMENT_BEGIN_TRIM) .'|' .
            preg_quote(self::COMMENT_BEGIN) . '|' .
            preg_quote(self::OUTPUT_BEGIN_TRIM) . '|' .
            preg_quote(self::OUTPUT_BEGIN) . '|' .
            preg_quote(self::RAW_BEGIN_TRIM) . '|' .
            preg_quote(self::RAW_BEGIN) . '|' .
            preg_quote(self::BLOCK_BEGIN_TRIM) . '|' .
            preg_quote(self::BLOCK_BEGIN) . ')/As', $this->source, $match,
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
            if ($token == self::COMMENT_BEGIN_TRIM ||
                $token == self::BLOCK_BEGIN_TRIM ||
                $token == self::OUTPUT_BEGIN_TRIM ||
                $token == self::RAW_BEGIN_TRIM) {
                $text = rtrim($text, " \t");
            }
            $tokens[] = new Token(Token::TEXT, $text, $this->line, $this->char);
        }

        $this->adjustLineChar($match[1]);

        switch ($token) {

        case self::COMMENT_BEGIN_TRIM:
        case self::COMMENT_BEGIN:
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

        case self::BLOCK_BEGIN_TRIM:
        case self::BLOCK_BEGIN:
            $tokens[] = new Token(
                Token::BLOCK_BEGIN, $token, $this->line, $this->char
            );
            $this->adjustLineChar($token);
            $this->position = self::POSITION_BLOCK;
            break;

        case self::OUTPUT_BEGIN_TRIM:
        case self::OUTPUT_BEGIN:
            $tokens[] = new Token(
                Token::OUTPUT_BEGIN, $token, $this->line, $this->char
            );
            $this->adjustLineChar($token);
            $this->position = self::POSITION_OUTPUT;
            break;

        case self::RAW_BEGIN_TRIM:
        case self::RAW_BEGIN:
            $tokens[] = new Token(
                Token::RAW_BEGIN, $token, $this->line, $this->char
            );
            $this->adjustLineChar($token);
            $this->position = self::POSITION_RAW;
            break;

        }

        return $tokens;
    }

    private function lexBlock() : array
    {
        $tokens = [];
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

    private function lexOutput() : array
    {
        $tokens = [];
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

    private function lexRaw() : array
    {
        $tokens = [];
        $match = null;

        if (preg_match('/(\s*)(' .
            preg_quote(self::RAW_END_TRIM) . '|' .
            preg_quote(self::RAW_END) . ')/A',
                $this->source, $match, null, $this->cursor)
        ) {
            if ($match[2] == self::RAW_END_TRIM) {
                $this->trim = true;
            }
            $this->cursor += strlen($match[0]);
            $this->adjustLineChar($match[1]);
            $tokens[] = new Token(
                Token::RAW_END, $match[2], $this->line, $this->char
            );
            $this->adjustLineChar($match[2]);
            $this->position = self::POSITION_TEXT;

            return $tokens;
        }
        return $this->lexExpression();
    }

    private function lexExpression() : array
    {
        $tokens = [];
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

        } elseif ($this->position == self::POSITION_RAW &&
            preg_match('/(.+?)\s*(' .
            preg_quote(self::RAW_END_TRIM) . '|' .
            preg_quote(self::RAW_END) . ')/As',
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

