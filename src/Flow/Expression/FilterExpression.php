<?php

namespace Flow\Expression;

use Flow\Expression;

final class FilterExpression extends Expression
{
    private $node;
    private $filters;

    public function __construct($node, $filters, $line)
    {
        parent::__construct($line);
        $this->node = $node;
        $this->filters = $filters;
    }

    public function appendFilter($filter)
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function prependFilter($filter)
    {
        array_unshift($this->filters, $filter);
        return $this;
    }

    public function compile($compiler, $indent = 0)
    {
        $stack = [];

        for ($i = count($this->filters) - 1; $i >= 0; --$i) {
            list($name, $arguments) = $this->filters[$i];
            $compiler->raw('$this->helper(\'' . $name . '\', ');
            $stack[] = $arguments;
        }

        $this->node->compile($compiler);

        foreach (array_reverse($stack) as $i => $arguments) {
            foreach ($arguments as $arg) {
                $compiler->raw(', ');
                $arg->compile($compiler);
            }
            $compiler->raw(')');
        }
    }
}


