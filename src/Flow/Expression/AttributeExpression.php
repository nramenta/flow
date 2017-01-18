<?php

namespace Flow\Expression;

use Flow\Expression;

final class AttributeExpression extends Expression
{
    private $node;
    private $attr;
    private $args;

    public function __construct($node, $attr, $args, $line)
    {
        parent::__construct($line);
        $this->node = $node;
        $this->attr = $attr;
        $this->args = $args;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('$this->getAttr(', $indent);
        $this->node->compile($compiler);
        $compiler->raw(', ');
        $this->attr->compile($compiler);
        if (is_array($this->args)) {
            $compiler->raw(', [');
            foreach ($this->args as $arg) {
                $arg->compile($compiler);
                $compiler->raw(', ');
            }
            $compiler->raw(']');
        } else {
            $compiler->raw(', false');
        }
        $compiler->raw(')');
    }
}

