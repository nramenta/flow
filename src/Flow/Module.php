<?php

namespace Flow;

final class Module
{
    private $path;
    private $class;
    private $extends;
    private $imports;
    private $blocks;
    private $macros;
    private $body;

    public function __construct($path, $class, $extends, $imports, $blocks, $macros, $body)
    {
        $this->path = $path;
        $this->class = $class;
        $this->extends = $extends;
        $this->imports = $imports;
        $this->blocks = $blocks;
        $this->macros = $macros;
        $this->body = $body;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw("<?php\n");
        $compiler->raw(
            '// ' . $this->path . ' ' . gmdate('Y-m-d H:i:s T', time()) .
            "\n", $indent
        );
        $compiler->raw("class $this->class extends \\Flow\\Template\n", $indent);
        $compiler->raw("{\n", $indent);

        $compiler->raw('const NAME = ', $indent + 1);
        $compiler->repr($this->path);
        $compiler->raw(";\n\n");

        $compiler->raw(
            'public function __construct($loader, $helpers = [])' . "\n",
            $indent + 1
        );
        $compiler->raw("{\n", $indent + 1);
        $compiler->raw(
            'parent::__construct($loader, $helpers);' . "\n",
            $indent + 2
        );

        // blocks constructor
        if (!empty($this->blocks)) {
            $compiler->raw('$this->blocks = [' . "\n", $indent + 2);
            foreach ($this->blocks as $name => $block) {
                $compiler->raw(
                    "'$name' => [\$this, 'block_{$name}'],\n", $indent + 3
                );
            }
            $compiler->raw("];\n", $indent + 2);
        }

        // macros constructor
        if (!empty($this->macros)) {
            $compiler->raw('$this->macros = [' . "\n", $indent + 2);
            foreach ($this->macros as $name => $macro) {
                $compiler->raw(
                    "'$name' => [\$this, 'macro_{$name}'],\n", $indent + 3
                );
            }
            $compiler->raw("];\n", $indent + 2);
        }

        // imports constructor
        if (!empty($this->imports)) {
            $compiler->raw('$this->imports = [' . "\n", $indent + 2);
            foreach ($this->imports as $import) {
                $import->compile($compiler, $indent + 3);
            }
            $compiler->raw("];\n", $indent + 2);
        }

        $compiler->raw("}\n\n", $indent + 1);

        $compiler->raw(
            'public function display' .
            '($context = [], $blocks = [], $macros = [],' .
            ' $imports = [])' .
            "\n", $indent + 1
        );
        $compiler->raw("{\n", $indent + 1);

        // extends
        if ($this->extends) {
            $this->extends->compile($compiler, $indent + 2);
        }
        $this->body->compile($compiler, $indent + 2);
        $compiler->raw("}\n", $indent + 1);

        foreach ($this->blocks as $block) {
            $block->compile($compiler, $indent + 1);
        }

        foreach ($this->macros as $macro) {
            $macro->compile($compiler, $indent + 1);
        }

        // line trace info
        $compiler->raw("\n");
        $compiler->raw('protected static $lines = ', $indent + 1);
        $compiler->raw($compiler->getTraceInfo(true) . ";\n");

        $compiler->raw("}\n");
        $compiler->raw('// end of ' . $this->path. "\n");
    }
}

