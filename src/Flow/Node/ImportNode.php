<?php

namespace Flow\Node;

use Flow\Node;

final class ImportNode extends Node
{
    private $module;
    private $import;

    public function __construct($module, $import, $line)
    {
        parent::__construct($line);
        $this->module = $module;
        $this->import = $import;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw("'$this->module' => ", $indent);
        $compiler->raw('$this->loadImport(');
        $this->import->compile($compiler);
        $compiler->raw("),\n");
    }
}

