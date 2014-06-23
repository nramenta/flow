<?php

namespace Flow;

class Compiler
{
    protected $fp;
    protected $node;
    protected $line;
    protected $trace;

    public function __construct($node)
    {
        $this->node  = $node;
        $this->line  = 1;
        $this->trace = array();
    }

    public function raw($raw, $indent = 0)
    {
        $this->line = $this->line + substr_count($raw, "\n");
        if (!fwrite($this->fp, str_repeat(' ', 4 * $indent) . $raw)) {
            throw new \RuntimeException(
                'failed writing to file: ' .  $this->target
            );
        }

        return $this;
    }

    public function repr($repr, $indent = 0)
    {
        $repr = $this->raw(var_export($repr, true), $indent);
    }

    public function compile($name, $target, $indent = 0)
    {
        if (!($this->fp = fopen($target, 'wb'))) {
            throw new \RuntimeException(
                'unable to create target file: ' . $target
            );
        }
        $this->node->compile($name, $this, $indent);
        fclose($this->fp);
    }

    public function pushContext($name, $indent = 0)
    {
        $this->raw('$this->pushContext($context, ', $indent);
        $this->repr($name);
        $this->raw(");\n");
        return $this;
    }

    public function popContext($name, $indent = 0)
    {
        $this->raw('$this->popContext($context, ', $indent);
        $this->repr($name);
        $this->raw(");\n");
        return $this;
    }

    public function addTraceInfo($node, $indent = 0, $line = true)
    {
        $this->raw(
            '/* line ' . $node->getLine() . " -> " . ($this->line + 1) .
            " */\n", $indent
        );
        if ($line) {
            $this->trace[$this->line] = $node->getLine();
        }
    }

    public function getTraceInfo($export = false)
    {
        if ($export) {
            return str_replace(
                array("\n", ' '), '', var_export($this->trace, true)
            );
        }
        return $this->trace;
    }

    public function __destruct()
    {
        if (is_resource($this->fp)) {
            fclose($this->fp);
        }
    }
}

