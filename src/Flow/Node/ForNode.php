<?php

namespace Flow\Node;

use Flow\Node;

final class ForNode extends Node
{
    private $seq;
    private $key;
    private $value;
    private $body;
    private $else;

    public function __construct($seq, $key, $value, $body, $else, $line)
    {
        parent::__construct($line);
        $this->seq = $seq;
        $this->key = $key;
        $this->value = $value;
        $this->body = $body;
        $this->else = $else;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);

        $compiler->pushContext('loop', $indent);
        if ($this->key) {
            $compiler->pushContext($this->key, $indent);
        }
        $compiler->pushContext($this->value, $indent);

        $else = false;
        if (!is_null($this->else)) {
            $compiler->raw('if (Flow\Helper::is_iterable(', $indent);
            $this->seq->compile($compiler);
            $compiler->raw(') && !Flow\Helper::is_empty(');
            $this->seq->compile($compiler);
            $compiler->raw(")) {\n");
            $else = true;
        }

        $compiler->raw(
            'foreach (($context[\'loop\'] = $this->iterate($context, ',
            $else ? ($indent + 1) : $indent
        );
        $this->seq->compile($compiler);

        if ($this->key) {
            $compiler->raw(
                ')) as $context[\'' . $this->key .
                '\'] => $context[\'' . $this->value . '\']) {' . "\n"
            );
        } else {
            $compiler->raw(
                ')) as $context[\'' . $this->value . '\']) {' . "\n"
            );
        }

        $this->body->compile($compiler, $else ? ($indent + 2) : ($indent + 1));

        $compiler->raw("}\n", $else ? ($indent + 1) : $indent);

        if ($else) {
            $compiler->raw("} else {\n", $indent);
            $this->else->compile($compiler, $indent + 1);
            $compiler->raw("}\n", $indent);
        }

        $compiler->popContext('loop', $indent);
        if ($this->key) {
            $compiler->popContext($this->key, $indent);
        }
        $compiler->popContext($this->value, $indent);
    }
}

