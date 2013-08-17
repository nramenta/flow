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

    public function compile($target, $indent = 0)
    {
        if (!($this->fp = fopen($target, 'wb'))) {
            throw new \RuntimeException(
                'unable to create target file: ' . $target
            );
        }
        $this->node->compile($this, $indent);
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

class Node
{
    protected $line;

    public function __construct($line)
    {
        $this->line = $line;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function addTraceInfo($compiler, $indent)
    {
        return $compiler->addTraceInfo($this, $indent);
    }

    public function compile($compiler, $indent = 0)
    {
    }
}

class NodeList extends Node
{
    protected $nodes;

    public function __construct($nodes, $line)
    {
        parent::__construct($line);
        $this->nodes = $nodes;
    }

    public function compile($compiler, $indent = 0)
    {
        foreach ($this->nodes as $node) {
            $node->compile($compiler, $indent);
        }
    }
}

class ModuleNode extends Node
{
    protected $name;
    protected $extends;
    protected $imports;
    protected $blocks;
    protected $macros;
    protected $body;

    public function __construct($name, $extends, $imports, $blocks, $macros,
        $body)
    {
        parent::__construct(0);
        $this->name = $name;
        $this->extends = $extends;
        $this->imports = $imports;
        $this->blocks = $blocks;
        $this->macros = $macros;
        $this->body = $body;
    }

    public function compile($compiler, $indent = 0)
    {
        $class = Loader::CLASS_PREFIX . md5($this->name);

        $compiler->raw("<?php\n");
        $compiler->raw(
            '// ' . $this->name . ' ' . gmdate('Y-m-d H:i:s T', time()) .
            "\n", $indent
        );
        $compiler->raw("class $class extends \\Flow\\Template\n", $indent);
        $compiler->raw("{\n", $indent);

        $compiler->raw('const NAME = ', $indent + 1);
        $compiler->repr($this->name);
        $compiler->raw(";\n\n");

        $compiler->raw(
            'public function __construct($loader, $helpers = array())' . "\n",
            $indent + 1
        );
        $compiler->raw("{\n", $indent + 1);
        $compiler->raw(
            'parent::__construct($loader, $helpers);' . "\n",
            $indent + 2
        );

        // blocks constructor
        if (!empty($this->blocks)) {
            $compiler->raw('$this->blocks = array(' . "\n", $indent + 2);
            foreach ($this->blocks as $name => $block) {
                $compiler->raw(
                    "'$name' => array(\$this, 'block_{$name}'),\n", $indent + 3
                );
            }
            $compiler->raw(");\n", $indent + 2);
        }

        // macros constructor
        if (!empty($this->macros)) {
            $compiler->raw('$this->macros = array(' . "\n", $indent + 2);
            foreach ($this->macros as $name => $macro) {
                $compiler->raw(
                    "'$name' => array(\$this, 'macro_{$name}'),\n", $indent + 3
                );
            }
            $compiler->raw(");\n", $indent + 2);
        }

        // imports constructor
        if (!empty($this->imports)) {
            $compiler->raw('$this->imports = array(' . "\n", $indent + 2);
            foreach ($this->imports as $module => $import) {
                $import->compile($compiler, $indent + 3);
            }
            $compiler->raw(");\n", $indent + 2);
        }

        $compiler->raw("}\n\n", $indent + 1);

        $compiler->raw(
            'public function display' .
            '($context = array(), $blocks = array(), $macros = array())' .
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
        $compiler->raw('// end of ' . $this->name . "\n");
    }
}

class BlockNode extends Node
{
    protected $name;
    protected $body;

    public function __construct($name, $body, $line)
    {
        parent::__construct($line);
        $this->name = $name;
        $this->body = $body;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw("\n");
        $compiler->addTraceInfo($this, $indent, false);
        $compiler->raw(
            'public function block_' . $this->name .
            '($context, $blocks = array(), $macros = array())' . "\n", $indent
        );
        $compiler->raw("{\n", $indent);
        $this->body->compile($compiler, $indent + 1);
        $compiler->raw("}\n", $indent);
    }
}

class ExtendsNode extends Node
{
    protected $parent;

    public function __construct($parent, $line)
    {
        parent::__construct($line);
        $this->parent = $parent;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw('$this->parent = $this->loadExtends(', $indent);
        $this->parent->compile($compiler);
        $compiler->raw(');' . "\n");

        $compiler->raw('if (isset($this->parent)) {' . "\n", $indent);
        $compiler->raw(
            'return $this->parent->display' .
            '($context, $blocks + $this->blocks, $macros + $this->macros);'.
            "\n", $indent + 1
        );
        $compiler->raw("}\n", $indent);
    }
}

class ImportNode extends Node
{
    protected $module;
    protected $import;

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

class BlockDisplayNode extends Node
{
    protected $name;

    public function __construct($name, $line)
    {
        parent::__construct($line);
        $this->name = $name;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw(
            '$this->displayBlock(\'' . $this->name .
            '\', $context, $blocks, $macros);' . "\n", $indent
        );
    }
}

class ParentNode extends Node
{
    protected $name;

    public function __construct($name, $line)
    {
        parent::__construct($line);
        $this->name = $name;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw(
            '$this->displayParent(\'' . $this->name .
            '\', $context, $blocks, $macros);' . "\n", $indent
        );
    }
}

class SetNode extends Node
{
    protected $name;
    protected $attrs;
    protected $value;

    public function __construct($name, $attrs, $value, $line)
    {
        parent::__construct($line);
        $this->name = $name;
        $this->attrs = $attrs;
        $this->value = $value;
    }

    public function compile($compiler, $indent = 0)
    {
        $name = "\$context['$this->name']";
        if ($this->value instanceof NodeList) {
            $compiler->raw("ob_start();\n", $indent);
            $this->value->compile($compiler);
            $compiler->raw(
                "if (!isset($name)) $name = array();\n" . "\n", $indent
            );
            $compiler->addTraceInfo($this, $indent);
            $compiler->raw("\$this->setAttr($name, array(", $indent);
            foreach ($this->attrs as $attr) {
                is_string($attr) ?
                    $compiler->repr($attr) : $attr->compile($compiler);
                $compiler->raw(', ');
            }
            $compiler->raw('), ob_get_clean());' . "\n");
        } else {
            $compiler->raw(
                "if (!isset($name)) $name = array();\n" . "\n", $indent
            );
            $compiler->addTraceInfo($this, $indent);
            $compiler->raw("\$this->setAttr($name, array(", $indent);
            foreach ($this->attrs as $attr) {
                is_string($attr) ?
                    $compiler->repr($attr) : $attr->compile($compiler);
                $compiler->raw(', ');
            }
            $compiler->raw('), ');
            $this->value->compile($compiler);
            $compiler->raw(");\n");
        }
    }
}

class TextNode extends Node
{
    protected $data;

    public function __construct($data, $line)
    {
        parent::__construct($line);
        $this->data = $data;
    }

    public function compile($compiler, $indent = 0)
    {
        if (!strlen($this->data)) return;
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw('echo ', $indent);
        $compiler->repr($this->data);
        $compiler->raw(";\n");
    }
}

class OutputNode extends Node
{
    protected $expr;

    public function __construct($expr, $line)
    {
        parent::__construct($line);
        $this->expr = $expr;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw('echo ', $indent);
        $this->expr->compile($compiler);
        $compiler->raw(";\n");
    }
}

class ForNode extends Node
{
    protected $seq;
    protected $key;
    protected $value;
    protected $body;
    protected $else;

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
            $compiler->raw('if (!Flow\Helper\is_empty(', $indent);
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

class BreakNode extends Node
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw("break;\n", $indent);
    }
}

class ContinueNode extends Node
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw("continue;\n", $indent);
    }
}

class IfNode extends Node
{
    protected $tests;
    protected $else;

    public function __construct($tests, $else, $line)
    {
        parent::__construct($line);
        $this->tests = $tests;
        $this->else = $else;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $idx = 0;
        foreach ($this->tests as $test) {
            $compiler->raw(($idx++ ? "} else" : '') . 'if (', $indent);
            $test[0]->compile($compiler);
            $compiler->raw(") {\n");
            $test[1]->compile($compiler, $indent + 1);
        }
        if (!is_null($this->else)) {
            $compiler->raw("} else {\n", $indent);
            $this->else->compile($compiler, $indent + 1);
        }
        $compiler->raw("}\n", $indent);
    }
}

class Expression extends Node
{
}

class ConditionalExpression extends Expression
{
    protected $expr1;
    protected $expr2;
    protected $expr3;

    public function __construct($expr1, $expr2, $expr3, $line)
    {
        parent::__construct($line);
        $this->expr1 = $expr1;
        $this->expr2 = $expr2;
        $this->expr3 = $expr3;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('((', $indent);
        $this->expr1->compile($compiler);
        $compiler->raw(') ? (');
        $this->expr2->compile($compiler);
        $compiler->raw(') : (');
        $this->expr3->compile($compiler);
        $compiler->raw('))');
    }
}

class BinaryExpression extends Expression
{
    protected $left;
    protected $right;

    public function __construct($left, $right, $line)
    {
        parent::__construct($line);
        $this->left = $left;
        $this->right = $right;
    }

    public function compile($compiler, $indent = 0)
    {
        $op = $this->operator($compiler);
        $compiler->raw('(', $indent);
        $this->left->compile($compiler);
        $compiler->raw(' ' . $op . ' ');
        $this->right->compile($compiler);
        $compiler->raw(')');
    }
}

class LogicalExpression extends BinaryExpression
{
}

class OrExpression extends LogicalExpression
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(($a = ', $indent);
        $this->left->compile($compiler);
        $compiler->raw(') ? ($a) : (');
        $this->right->compile($compiler);
        $compiler->raw('))');
    }
}

class XorExpression extends BinaryExpression
{
    public function operator()
    {
        return 'xor';
    }
}

class AndExpression extends LogicalExpression
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(!($a = ', $indent);
        $this->left->compile($compiler);
        $compiler->raw(') ? ($a) : (');
        $this->right->compile($compiler);
        $compiler->raw('))');
    }
}

class InclusionExpression extends LogicalExpression
{
    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(in_array(', $indent);
        $this->left->compile($compiler);
        $compiler->raw(', (array)');
        $this->right->compile($compiler);
        $compiler->raw('))');
    }
}

class AddExpression extends BinaryExpression
{
    public function operator()
    {
        return '+';
    }
}

class SubExpression extends BinaryExpression
{
    public function operator()
    {
        return '-';
    }
}

class MulExpression extends BinaryExpression
{
    public function operator()
    {
        return '*';
    }
}

class DivExpression extends BinaryExpression
{
    public function operator()
    {
        return '/';
    }
}

class ModExpression extends BinaryExpression
{
    public function operator()
    {
        return '%';
    }
}

class ConcatExpression extends BinaryExpression
{
    public function operator()
    {
        return '.';
    }
}

class CompareExpression extends Expression
{
    protected $expr;
    protected $ops;

    public function __construct($expr, $ops, $line)
    {
        parent::__construct($line);
        $this->expr = $expr;
        $this->ops = $ops;
    }

    public function compile($compiler, $indent = 0)
    {
        $this->expr->compile($compiler);
        $i = 0;
        foreach ($this->ops as $op) {
            if ($i) {
                $compiler->raw(' && ($tmp' . $i);
            }
            list($op, $node) = $op;
            $compiler->raw(' ' . ($op == '=' ? '==' : $op) . ' ');
            $compiler->raw('($tmp' . ++$i . ' = ');
            $node->compile($compiler);
            $compiler->raw(')');
        }
        if ($i > 1) {
            $compiler->raw(str_repeat(')', $i - 1));
        }
    }
}

class UnaryExpression extends Expression
{
    protected $node;

    public function __construct($node, $line)
    {
        parent::__construct($line);
        $this->node = $node;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(', $indent);
        $this->operator($compiler);
        $compiler->raw('(');
        $this->node->compile($compiler);
        $compiler->raw('))');
    }
}

class NotExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('!');
    }
}

class NegExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('-');
    }
}

class PosExpression extends UnaryExpression
{
    public function operator($compiler)
    {
        $compiler->raw('+');
    }
}

class ArrayExpression extends Expression
{
    protected $elements;

    public function __construct($elements, $line)
    {
        parent::__construct($line);
        $this->elements = $elements;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('array(', $indent);
        foreach ($this->elements as $node) {
            if (is_array($node)) {
                $node[0]->compile($compiler);
                $compiler->raw(' => ');
                $node[1]->compile($compiler);
            } else {
                $node->compile($compiler);
            }
            $compiler->raw(',');
        }
        $compiler->raw(')');
    }
}

class ConstantExpression extends Expression
{
    protected $value;

    public function __construct($value, $line)
    {
        parent::__construct($line);
        $this->value = $value;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->repr($this->value);
    }
}

class StringExpression extends Expression
{
    protected $value;

    public function __construct($value, $line)
    {
        parent::__construct($line);
        $this->value = $value;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->repr($this->value);
    }
}

class NameExpression extends Expression
{
    protected $name;

    public function __construct($name, $line)
    {
        parent::__construct($line);
        $this->name = $name;
    }

    public function raw($compiler, $indent = 0)
    {
        $compiler->raw($this->name, $indent);
    }

    public function repr($compiler, $indent = 0)
    {
        $compiler->repr($this->name, $indent);
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('(isset($context[\'' . $this->name . '\']) ? ', $indent);
        $compiler->raw('$context[\'' . $this->name . '\'] : null)');
    }
}

class AttributeExpression extends Expression
{
    protected $node;
    protected $attr;
    protected $args;

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
            $compiler->raw(', array(');
            foreach ($this->args as $arg) {
                $arg->compile($compiler);
                $compiler->raw(', ');
            }
            $compiler->raw(')');
        } else {
            $compiler->raw(', false');
        }
        $compiler->raw(')');
    }
}

class FunctionCallExpression extends Expression
{
    protected $node;
    protected $args;

    public function __construct($node, $args, $line)
    {
        parent::__construct($line);
        $this->node = $node;
        $this->args = $args;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->raw('$this->helper(');
        $this->node->repr($compiler);
        foreach ($this->args as $arg) {
            $compiler->raw(', ');
            $arg->compile($compiler);
        }
        $compiler->raw(')');
    }

}

class FilterExpression extends Expression
{
    protected $node;
    protected $filters;
    protected $autoEscape;

    public function __construct($node, $filters, $autoEscape, $line)
    {
        parent::__construct($line);
        $this->node = $node;
        $this->filters = $filters;
        $this->autoEscape = $autoEscape;
    }

    public function isRaw()
    {
        return in_array('raw', $this->filters);
    }

    public function setAutoEscape($autoEscape = true)
    {
        $this->autoEscape = $autoEscape;
        return $this;
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
        static $raw = 'raw';

        $safe = false;
        $postponed = array();

        foreach ($this->filters as $i => $filter) {
            if ($filter[0] == $raw) {
                $safe = true;
                break;
            }
        }

        if ($this->autoEscape && !$safe) {
            $this->appendFilter(array('escape', array()));
        }

        for ($i = count($this->filters) - 1; $i >= 0; --$i) {
            if ($this->filters[$i] === 'raw') continue;
            list($name, $arguments) = $this->filters[$i];
            if ($name == $raw) continue;
            $compiler->raw('$this->helper(\'' . $name . '\', ');
            $postponed[] = $arguments;
        }

        $this->node->compile($compiler);

        foreach (array_reverse($postponed) as $i => $arguments) {
            foreach ($arguments as $arg) {
                $compiler->raw(', ');
                $arg->compile($compiler);
            }
            $compiler->raw(')');
        }
    }
}

class MacroNode extends Node
{
    protected $name;
    protected $args;
    protected $body;

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
            '($_context = array(), $macros = array())' . "\n", $indent
        );
        $compiler->raw("{\n", $indent);

        $compiler->raw('$context = $_context + array(' . "\n", $indent + 1);
        $i = 0;
        foreach ($this->args as $key => $val) {
            $compiler->raw(
                "'$key' => !isset(\$_context['$key']) &&" .
                " isset(\$_context[$i]) ? \$_context[$i] : ",
                $indent + 2
            );
            $val->compile($compiler);
            $compiler->raw(",\n");
            $i += 1;
        }
        $compiler->raw(");\n", $indent + 1);

        $compiler->raw("ob_start();\n", $indent + 1);
        $this->body->compile($compiler, $indent + 1);
        $compiler->raw("return ob_get_clean();\n", $indent + 1);
        $compiler->raw("}\n", $indent);
    }
}

class MacroExpression extends Expression
{
    protected $module;
    protected $name;
    protected $args;

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
            '$this->expandMacro(\'' . $this->name . '\', array(', $indent
        );
        foreach ($this->args as $key => $val) {
            $compiler->raw("'$key' => ");
            $val->compile($compiler);
            $compiler->raw(',');
        }
        if (isset($this->module)) {
            $compiler->raw(
                '), $this->imports[\'' . $this->module . '\']->macros)'
            );
        } else {
            $compiler->raw('), $macros)');
        }
    }
}

class IncludeNode extends Node
{
    protected $include;

    public function __construct($include, $line)
    {
        parent::__construct($line);
        $this->include = $include;
    }

    public function compile($compiler, $indent = 0)
    {
        $compiler->addTraceInfo($this, $indent);
        $compiler->raw('$this->loadInclude(', $indent);
        $this->include->compile($compiler);
        $compiler->raw(')->display($context);' . "\n");
    }
}

