<?php

namespace Flow\Node;

use Flow\Node;

final class MacroNode extends Node
{
    private $name;
    private $args;
    private $body;

    public function __construct($name, $args, $body, $line)
    {
        parent::__construct($line);
        $this->name = $name;
        $this->args = $args;
        $this->body = $body;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw("\n");
        $compiler->addTraceInfo($this, $indent, false);
        $compiler->raw(
            'public function macro_' . $this->name .
            '($params = [], $context = [], $macros = [],' .
            ' $imports = [], $block = null)' .
            "\n", $indent
        );
        $compiler->raw("{\n", $indent);

        $compiler->raw('$context = $params + [' . "\n", $indent + 1);
        $i = 0;
        foreach ($this->args as $key => $val) {
            $compiler->raw(
                "'$key' => !isset(\$params['$key']) &&" .
                " isset(\$params[$i]) ? \$params[$i] : ",
                $indent + 2
            );
            $val->compile($compiler);
            $compiler->raw(",\n");
            $i += 1;
        }
        $compiler->raw("] + \$context;\n", $indent + 1);

        $this->body->compile($compiler, $indent + 1);
        $compiler->raw("}\n", $indent);
    }
}

