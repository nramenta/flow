<?php

namespace Flow\Node;

use Flow\Node;

final class CallNode extends Node
{
    private $module;
    private $name;
    private $args;

    public function __construct($module, $name, $args, $line)
    {
        parent::__construct($line);
        $this->module = $module;
        $this->name = $name;
        $this->args = $args;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw(
            'echo $this->expandMacro(\'' . $this->module . '\', \'' . $this->name .
            '\', array(', $indent
        );
        foreach ($this->args as $key => $val) {
            $compiler->raw("'$key' => ");
            $val->compile($compiler);
            $compiler->raw(',');
        }
        if (isset($this->module)) {
            $compiler->raw(
                '), $context, $macros, $imports)'
            );
        } else {
            $compiler->raw('), $context, $macros, $imports);');
        }
    }
}

