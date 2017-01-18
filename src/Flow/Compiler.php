<?php

namespace Flow;

final class Compiler
{
    private $result;
    private $module;
    private $line;
    private $trace;

    public function __construct(Module $module)
    {
        $this->result = '';
        $this->module = $module;
        $this->line   = 1;
        $this->trace  = [];
    }

    private function write($string)
    {
        $this->result .= $string;
        return $this;
    }

    public function raw($raw, $indent = 0)
    {
        $this->line = $this->line + substr_count($raw, "\n");
        $this->write(str_repeat(' ', 4 * $indent) . $raw);
        return $this;
    }

    public function repr($repr, $indent = 0)
    {
        $this->raw(var_export($repr, true), $indent);
    }

    public function compile()
    {
        $this->module->compile($this);
        return $this->result;
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
                ["\n", ' '], '', var_export($this->trace, true)
            );
        }
        return $this->trace;
    }
}

