<?php

namespace Flow\Node;

use Flow\Node;

final class ExtendsNode extends Node
{
    private $parent;
    private $params;

    public function __construct($parent, $params, $line)
    {
        parent::__construct($line);
        $this->parent = $parent;
        $this->params = $params;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw('$this->parent = $this->loadExtends(', $indent);
        $this->parent->compile($compiler);
        $compiler->raw(');' . "\n");

        $compiler->raw('if (isset($this->parent)) {' . "\n", $indent);
        if ($this->params instanceof ArrayExpression) {
            $compiler->raw('$context = ', $indent + 1);
            $this->params->compile($compiler);
            $compiler->raw(' + $context;' . "\n");
        }
        $compiler->raw(
            'return $this->parent->display' .
            '($context, $blocks + $this->blocks, $macros + $this->macros,' .
            ' $imports + $this->imports);'.
            "\n", $indent + 1
        );
        $compiler->raw("}\n", $indent);
    }
}

