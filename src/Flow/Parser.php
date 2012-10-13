<?php

namespace Flow;

class Parser
{
    protected $stream;
    protected $template;
    protected $extends;
    protected $blocks;
    protected $currentBlock;
    protected $tags;
    protected $inForLoop;
    protected $macros;
    protected $inMacro;
    protected $imports;
    protected $autoEscape;

    public function __construct($stream, $template)
    {
        $this->stream   = $stream;
        $this->template = $template;
        $this->extends  = null;
        $this->blocks   = array();

        $this->currentBlock = array();

        $this->tags = array(
            'if'            => 'parseIf',
            'for'           => 'parseFor',
            'break'         => 'parseBreak',
            'continue'      => 'parseContinue',
            'extends'       => 'parseExtends',
            'set'           => 'parseSet',
            'block'         => 'parseBlock',
            'parent'        => 'parseParent',
            'autoescape'    => 'parseAutoEscape',
            'endautoescape' => 'parseEndAutoEscape',
            'macro'         => 'parseMacro',
            'import'        => 'parseImport',
            'include'       => 'parseInclude',
        );

        $this->inForLoop  = 0;
        $this->macros     = array();
        $this->inMacro    = false;
        $this->imports    = array();
        $this->autoEscape = array(false);
    }

    public function parse()
    {
        $body = $this->subparse();
        return new ModuleNode(
            $this->template, $this->extends, $this->imports, $this->blocks,
            $this->macros, $body
        );
    }

    protected function subparse($test = null, $next = false)
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $nodes = array();
        while (!$this->stream->isEOS()) {
            switch ($this->stream->getCurrentToken()->getType()) {
            case Token::TEXT_TYPE:
                $token = $this->stream->next();
                $nodes[] = new TextNode($token->getValue(), $token->getLine());
                break;
            case Token::BLOCK_START_TYPE:
                $this->stream->next();
                $token = $this->stream->getCurrentToken();
                if ($token->getType() !== Token::NAME_TYPE) {
                    throw new SyntaxError(
                        sprintf(
                            'expected directive, unexpected %s "%s"',
                            $token->getType(true, false), $token->getValue()
                        ),
                        $token->getLine(), $this->template
                    );
                }
                if (!is_null($test) && $token->test($test)) {
                    if (is_bool($next)) {
                        $next = $next ? 1 : 0;
                    }
                    $this->stream->skip($next);
                    return new NodeList($nodes, $line);
                }

                if (!in_array($token->getValue(), array_keys($this->tags))) {
                    throw new SyntaxError(
                        sprintf('unknown construct "%s"', $token->getValue()),
                        $token->getLine(), $this->template
                    );
                }
                $this->stream->next();
                if (isset($this->tags[$token->getValue()]) &&
                    is_callable(array($this, $this->tags[$token->getValue()]))
                ) {
                    $node = call_user_func(
                        array($this, $this->tags[$token->getValue()]), $token
                    );
                } else {
                    throw new SyntaxError(
                        sprintf(
                            "missing construct handler '%s'",
                            $token->getValue()
                        ),
                        $token->getLine(), $this->template
                    );
                }
                if (!is_null($node)) {
                    $nodes[] = $node;
                }
                break;

            case Token::OUTPUT_START_TYPE:
                $token = $this->stream->next();
                $expr = $this->parseExpression();
                $autoEscape = $this->autoEscape[count($this->autoEscape) - 1];
                if ($autoEscape) {
                    $filters = array();
                    if ($expr instanceof FilterExpression) {
                        $expr->setAutoEscape(true);
                    } else {
                        $expr = new FilterExpression(
                            $expr, $filters, true, $token->getLine()
                        );
                    }
                }
                $nodes[] = $this->parseIfModifier(
                    $token, new OutputNode($expr, $token->getLine())
                );
                $this->stream->expect(Token::OUTPUT_END_TYPE);
                break;
            default:
                throw new SyntaxError(
                    'parser ended up in unsupported state',
                    $line, $this->template
                );
            }
        }
        return new NodeList($nodes, $line);
    }

    protected function parseName($expect = true, $match = null)
    {
        static $constants = array('true', 'false', 'null');
        static $operators = array('not', 'in');

        if ($this->stream->test(Token::CONSTANT_TYPE, $constants)) {
            return $this->stream->expect(Token::CONSTANT_TYPE, $match);
        } elseif ($this->stream->test(Token::OPERATOR_TYPE, $operators)) {
            return $this->stream->expect(Token::OPERATOR_TYPE, $match);
        } elseif ($expect or $this->stream->test(Token::NAME_TYPE)) {
            return $this->stream->expect(Token::NAME_TYPE, $match);
        }
    }

    protected function parseIf($token)
    {
        $line = $token->getLine();
        $expr = $this->parseExpression();
        $this->stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->subparse(array('elseif', 'else', 'endif'));
        $tests = array(array($expr, $body));
        $else = null;

        $end = false;
        while (!$end) {
            switch ($this->stream->next()->getValue()) {
            case 'elseif':
                $expr = $this->parseExpression();
                $this->stream->expect(Token::BLOCK_END_TYPE);
                $body = $this->subparse(array('elseif', 'else', 'endif'));
                $tests[] = array($expr, $body);
                break;
            case 'else':
                $this->stream->expect(Token::BLOCK_END_TYPE);
                $else = $this->subparse(array('endif'));
                break;
            case 'endif':
                $this->stream->expect(Token::BLOCK_END_TYPE);
                $end = true;
                break;
            default:
                throw new SyntaxError(
                    'malformed if statement', $line, $this->template
                );
                break;
            }
        }
        return new IfNode($tests, $else, $line);
    }

    protected function parseIfModifier($token, $node)
    {
        static $modifiers = array('if', 'unless');

        if ($this->stream->test($modifiers)) {
            $statement = $this->stream->expect($modifiers)->getValue();
            $test_expr = $this->parseExpression();
            if ($statement == 'if') {
                $node = new IfNode(
                    array(array($test_expr, $node)), null, $token->getLine()
                );
            } elseif ($statement == 'unless') {
                $node = new IfNode(
                    array(array(
                        new NotExpression($test_expr, $token->getLine()), $node
                    )), null, $token->getLine()
                );
            }
        }
        return $node;
    }

    protected function parseFor($token)
    {
        $this->inForLoop++;
        $line = $token->getLine();
        $key = null;
        $value = $this->stream->expect(Token::NAME_TYPE)->getValue();
        if ($this->stream->consume(Token::OPERATOR_TYPE, ',')) {
            $key = $value;
            $value = $this->stream->expect(Token::NAME_TYPE)->getValue();
        }
        $this->stream->expect(Token::OPERATOR_TYPE, 'in');
        $seq = $this->parseExpression();
        $this->stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->subparse(array('else', 'endfor'));
        $this->inForLoop--;
        if ($this->stream->next()->getValue() == 'else') {
            $this->stream->expect(Token::BLOCK_END_TYPE);
            $else = $this->subparse('endfor', true);
        } else {
            $else = null;
        }
        $this->stream->expect(Token::BLOCK_END_TYPE);
        return new ForNode($seq, $key, $value, $body, $else, $line);
    }

    protected function parseBreak($token)
    {
        if (!$this->inForLoop) {
            throw new SyntaxError(
                'unexpected break, not in for loop',
                $token->getLine(), $this->template
            );
        }
        $node = $this->parseIfModifier(
            $token, new BreakNode($token->getLine())
        );
        $this->stream->expect(Token::BLOCK_END_TYPE);
        return $node;
    }

    protected function parseContinue($token)
    {
        if (!$this->inForLoop) {
            throw new SyntaxError(
                'unexpected continue, not in for loop',
                $token->getLine(), $this->template
            );
        }
        $node = $this->parseIfModifier(
            $token, new ContinueNode($token->getLine())
        );
        $this->stream->expect(Token::BLOCK_END_TYPE);
        return $node;
    }

    protected function parseExtends($token)
    {
        if (!is_null($this->extends)) {
            throw new SyntaxError(
                'multiple extend tags',
                $token->getLine(), $this->template
            );
        }

        if (!empty($this->currentBlock)) {
            throw new SyntaxError(
                'cannot declare extends inside blocks',
                $token->getLine(), $this->template
            );
        }

        if ($this->inMacro) {
            throw new SyntaxError(
                'cannot declare extends inside macros',
                $token->getLine(), $this->template
            );
        }

        $this->extends = $this->parseExpression();
        $this->stream->expect(Token::BLOCK_END_TYPE);
        return null;
    }

    protected function parseSet($token)
    {
        $name = $this->stream->expect(Token::NAME_TYPE)->getValue();
        if ($this->stream->consume(Token::OPERATOR_TYPE, '=')) {
            $value = $this->parseExpression();
            $this->stream->expect(Token::BLOCK_END_TYPE);
            $node = $this->parseIfModifier(
                $token, new SetNode($name, $value, $token->getLine())
            );
        } else {
            $this->stream->expect(Token::BLOCK_END_TYPE);
            $body = $this->subparse('endset', true);
            $this->stream->expect(Token::BLOCK_END_TYPE);
            $node = new SetNode($name, $body, $token->getLine());
        }
        return $node;
    }

    protected function parseBlock($token)
    {
        if ($this->inMacro) {
            throw new SyntaxError(
                'cannot declare blocks inside macros',
                $token->getLine(), $this->template
            );
        }
        $name = $this->parseName()->getValue();
        if (isset($this->blocks[$name])) {
            throw new SyntaxError(
                sprintf('block "%s" already defined', $name),
                $token->getLine(), $this->template
            );
        }
        array_push($this->currentBlock, $name);
        if ($this->stream->consume(Token::BLOCK_END_TYPE)) {
            $body = $this->subparse('endblock', true);
            $this->parseName(false, $name);
        } else {
            $expr = $this->parseExpression();
            $autoEscape = $this->autoEscape[count($this->autoEscape) - 1];
            if ($autoEscape) {
                $filters = array();
                if ($expr instanceof FilterExpression) {
                    $expr->setAutoEscape(true);
                } else {
                    $expr = new FilterExpression(
                        $expr, $filters, true, $token->getLine()
                    );
                }
            }
            $body = new OutputNode($expr, $token->getLine());
        }
        $this->stream->expect(Token::BLOCK_END_TYPE);
        array_pop($this->currentBlock);
        $this->blocks[$name] = new BlockNode($name, $body, $token->getLine());
        return new BlockDisplayNode($name, $token->getLine());
    }

    protected function parseParent($token)
    {
        if ($this->inMacro) {
            throw new SyntaxError(
                'cannot call parent block inside macros',
                $token->getLine(), $this->template
            );
        }

        if (empty($this->currentBlock)) {
            throw new SyntaxError(
                'parent must be inside a block',
                $token->getLine(), $this->template
            );
        }

        $node = $this->parseIfModifier(
            $token,
            new ParentNode($this->currentBlock[count($this->currentBlock) - 1],
            $token->getLine())
        );
        $this->stream->expect(Token::BLOCK_END_TYPE);
        return $node;
    }

    protected function parseAutoEscape($token)
    {
        $autoEscape = $this->stream->expect(array('on', 'off'))->getValue();
        $this->stream->expect(Token::BLOCK_END_TYPE);
        array_push($this->autoEscape, $autoEscape == 'on' ? true : false);
        return null;
    }

    protected function parseEndAutoEscape($token)
    {
        if (empty($this->autoEscape)) {
            throw new SyntaxError(
                'unmatched endautoescape tag',
                $token->getLine(), $this->template
            );
        }
        array_pop($this->autoEscape);
        $this->stream->expect(Token::BLOCK_END_TYPE);
        return null;
    }

    protected function parseMacro($token)
    {
        if (!empty($this->currentBlock)) {
            throw new SyntaxError(
                'cannot declare macros inside blocks',
                $token->getLine(), $this->template
            );
        }

        if ($this->inMacro) {
            throw new SyntaxError(
                'cannot declare macros inside another macro',
                $token->getLine(), $this->template
            );
        }

        $this->inMacro = true;
        $name = $this->parseName()->getValue();
        $args = array();
        if ($this->stream->consume(Token::OPERATOR_TYPE, '(')) {
            while (!$this->stream->test(Token::OPERATOR_TYPE, ')')) {
                if (!empty($args)) {
                    $this->stream->expect(Token::OPERATOR_TYPE, ',');
                    if ($this->stream->test(Token::OPERATOR_TYPE, ')'))
                        break;
                }
                $args[] = $this->parseName()->getValue();
            }
            $this->stream->expect(Token::OPERATOR_TYPE, ')');
        }
        $this->stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->subparse('endmacro', true);
        $this->stream->expect(Token::BLOCK_END_TYPE);
        $this->macros[$name] = new MacroNode(
            $name, $args, $body, $token->getLine()
        );
        $this->inMacro = false;
    }

    protected function parseImport($token)
    {
        $import = $this->parseExpression();
        $this->stream->expect(Token::NAME_TYPE, 'as');
        $module = $this->stream->expect(Token::NAME_TYPE)->getValue();
        $this->stream->expect(Token::BLOCK_END_TYPE);
        $this->imports[$module] = $import;
    }

    protected function parseInclude($token)
    {
        $include = $this->parseExpression();
        $node = $this->parseIfModifier(
            $token, new IncludeNode($include, $token->getLine())
        );
        $this->stream->expect(Token::BLOCK_END_TYPE);
        return $node;
    }

    protected function parseExpression()
    {
        return $this->parseConditionalExpression();
    }

    protected function parseConditionalExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $expr1 = $this->parseXorExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, '?')) {
            $expr2 = $this->parseOrExpression();
            $this->stream->expect(Token::OPERATOR_TYPE, ':');
            $expr3 = $this->parseConditionalExpression();
            $expr1 = new ConditionalExpression($expr1, $expr2, $expr3, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $expr1;
    }

    protected function parseXorExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseOrExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, 'xor')) {
            $right = $this->parseOrExpression();
            $left = new XorExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseOrExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseAndExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, 'or')) {
            $right = $this->parseAndExpression();
            $left = new OrExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseAndExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseNotExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, 'and')) {
            $right = $this->parseNotExpression();
            $left = new AndExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseNotExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        if ($this->stream->consume(Token::OPERATOR_TYPE, 'not')) {
            $node = $this->parseNotExpression();
            return new NotExpression($node, $line);
        }
        return $this->parseInclusionExpression();
    }

    protected function parseInclusionExpression()
    {
        static $operators = array('not', 'in');

        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseCompareExpression();
        while ($this->stream->test(Token::OPERATOR_TYPE, $operators)) {
            if ($this->stream->consume(Token::OPERATOR_TYPE, 'not')) {
                $this->stream->expect(Token::OPERATOR_TYPE, 'in');
                $right = $this->parseCompareExpression();
                $left = new NotExpression(
                    new InclusionExpression($left, $right, $line), $line
                );
            } else {
                $this->stream->expect(Token::OPERATOR_TYPE, 'in');
                $right = $this->parseCompareExpression();
                $left = new InclusionExpression($left, $right, $line);
            }
        }
        return $left;
    }

    protected function parseCompareExpression()
    {
        static $operators = array(
            '!==', '===', '==', '!=', '<>', '<', '>', '>=', '<='
        );
        $line = $this->stream->getCurrentToken()->getLine();
        $expr = $this->parseConcatExpression();
        $ops = array();
        while ($this->stream->test(Token::OPERATOR_TYPE, $operators)) {
            $ops[] = array(
                $this->stream->next()->getValue(),
                $this->parseAddExpression()
            );
        }

        if (empty($ops)) {
            return $expr;
        }
        return new CompareExpression($expr, $ops, $line);
    }

    protected function parseConcatExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseAddExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, '..')) {
            $right = $this->parseAddExpression();
            $left = new ConcatExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseAddExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseSubExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, '+')) {
            $right = $this->parseSubExpression();
            $left = new AddExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseSubExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseMulExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, '-')) {
            $right = $this->parseMulExpression();
            $left = new SubExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseMulExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseDivExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, '*')) {
            $right = $this->parseDivExpression();
            $left = new MulExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseDivExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseModExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, '/')) {
            $right = $this->parseModExpression();
            $left = new DivExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseModExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseUnaryExpression();
        while ($this->stream->consume(Token::OPERATOR_TYPE, '%')) {
            $right = $this->parseUnaryExpression();
            $left = new ModExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseUnaryExpression()
    {
        if ($this->stream->test(Token::OPERATOR_TYPE, array('-', '+'))) {
            switch ($this->stream->getCurrentToken()->getValue()) {
            case '-':
                return $this->parseNegExpression();
            case '+':
                return $this->parsePosExpression();
            }
        }
        return $this->parsePrimaryExpression();
    }

    protected function parseNegExpression()
    {
        $token = $this->stream->next();
        $node = $this->parseUnaryExpression();
        return new NegExpression($node, $token->getLine());
    }

    protected function parsePosExpression()
    {
        $token = $this->stream->next();
        $node = $this->parseUnaryExpression();
        return new PosExpression($node, $token->getLine());
    }

    protected function parsePrimaryExpression()
    {
        $token = $this->stream->getCurrentToken();
        switch ($token->getType()) {
        case Token::CONSTANT_TYPE:
            $this->stream->next();
            switch ($token->getValue()) {
            case 'true':
                $node = new ConstantExpression(true, $token->getLine());
                break;
            case 'false':
                $node = new ConstantExpression(false, $token->getLine());
                break;
            case 'null':
                $node = new ConstantExpression(null, $token->getLine());
                break;
            }
            break;
        case Token::NAME_TYPE:
            $this->stream->next();
            $node = new NameExpression($token->getValue(), $token->getLine());
            if ($this->stream->test(Token::OPERATOR_TYPE, '(')) {
                $node = $this->parseFunctionCallExpression($node);
            }
            break;
        case Token::NUMBER_TYPE:
            $this->stream->next();
            if (preg_match('/\./', $token->getValue())) {
                $node = new ConstantExpression(
                    floatval($token->getValue()), $token->getLine()
                );
            } else {
                $node = new ConstantExpression(
                    intval($token->getValue()), $token->getLine()
                );
            }
            break;
        case Token::STRING_TYPE:
            $this->stream->next();
            $node = new StringExpression(
                strval($token->getValue()), $token->getLine()
            );
            break;
        default:
            if ($this->stream->consume(Token::OPERATOR_TYPE, '@')) {
                $node = $this->parseMacroExpression($token);
            } elseif ($this->stream->consume(Token::OPERATOR_TYPE, '[')) {
                $node = $this->parseArrayExpression();
                $this->stream->expect(Token::OPERATOR_TYPE, ']');
            } elseif ($this->stream->consume(Token::OPERATOR_TYPE, '(')) {
                $node = $this->parseExpression();
                $this->stream->expect(Token::OPERATOR_TYPE, ')');
            } else {
                throw new SyntaxError(
                    sprintf(
                        'expected expression, unexpected %s "%s"',
                        $token->getType(true, false), $token->getValue()
                    ),
                    $token->getLine(), $this->template
                );
            }
        }
        return $this->parsePostfixExpression($node);
    }

    protected function parseFunctionCallExpression($node)
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $this->stream->expect(Token::OPERATOR_TYPE, '(');
        $args = array();
        while (!$this->stream->test(Token::OPERATOR_TYPE, ')')) {
            if (!empty($args)) {
                $this->stream->expect(Token::OPERATOR_TYPE, ',');
                if ($this->stream->test(Token::OPERATOR_TYPE, ')')) break;
            }
            $args[] = $this->parseExpression();
        }
        $this->stream->expect(Token::OPERATOR_TYPE, ')');
        return new FunctionCallExpression($node, $args, $line);
    }

    protected function parseMacroExpression($token)
    {
        $module = null;
        $name = $this->parseName()->getValue();
        if ($this->stream->consume(Token::OPERATOR_TYPE, '.')) {
            $module = $name;
            $name = $this->parseName()->getValue();
        }
        $args = array();

        if ($this->stream->consume(Token::OPERATOR_TYPE, '(')) {
            while (!$this->stream->test(Token::OPERATOR_TYPE, ')')) {
                if (!empty($args)) {
                    $this->stream->expect(Token::OPERATOR_TYPE, ',');
                    if ($this->stream->test(Token::OPERATOR_TYPE, ')'))
                        break;
                }
                if ($this->stream->test(Token::NAME_TYPE) &&
                    $this->stream->look()->test(Token::OPERATOR_TYPE, '=')
                ) {
                    $key = $this->stream->expect(Token::NAME_TYPE)->getValue();
                    $this->stream->expect(Token::OPERATOR_TYPE, '=');
                    $val = $this->parseExpression();
                    $args[$key] = $val;
                } else {
                    $args[] = $this->parseExpression();
                }
            }
            $this->stream->expect(Token::OPERATOR_TYPE, ')');
        }
        return new MacroExpression($module, $name, $args, $token->getLine());
    }


    protected function parseArrayExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $elements = array();
        do {
            $token = $this->stream->getCurrentToken();
            if ($token->test(Token::OPERATOR_TYPE, ']')) break;
            if ($token->test(Token::NAME_TYPE) ||
                $token->test(Token::STRING_TYPE) ||
                $token->test(Token::NUMBER_TYPE)
            ) {
                if ($token->test(Token::NAME_TYPE) ||
                    $token->test(Token::STRING_TYPE)
                ) {
                    $key = new ConstantExpression(
                        strval($token->getValue()), $line
                    );
                } else {
                    if (preg_match('/\./', $token->getValue())) {
                        $key = new ConstantExpression(
                            floatval($token->getValue()), $line
                        );
                    } else {
                        $key = new ConstantExpression(
                            intval($token->getValue()), $line
                        );
                    }
                }
                $this->stream->next();
                if ($this->stream->consume(Token::OPERATOR_TYPE, array('=>'))) {
                    $element = $this->parseExpression();
                    $elements[] = array($key, $element);
                } else {
                    $elements[] = $key;
                }
            } else {
                $elements[] = $this->parseExpression();
            }
            $this->stream->consume(Token::OPERATOR_TYPE, ',');
        } while (!$this->stream->test(Token::OPERATOR_TYPE, ']'));
        return new ArrayExpression($elements, $line);
    }

    protected function parsePostfixExpression($node)
    {
        $stop = false;
        while (!$stop &&
            $this->stream->getCurrentToken()->getType() == Token::OPERATOR_TYPE
        ) {
            switch ($this->stream->getCurrentToken()->getValue()) {
            case '.':
            case '[':
                $node = $this->parseAttributeExpression($node);
                break;
            case '|':
                $node = $this->parseFilterExpression($node);
                break;
            default:
                $stop = true;
                break;
            }
        }
        return $node;
    }

    protected function parseAttributeExpression($node)
    {
        $token = $this->stream->getCurrentToken();
        if ($this->stream->consume(Token::OPERATOR_TYPE, '.')) {
            $attr = new ConstantExpression(
                $this->stream->expect(Token::NAME_TYPE)->getValue(),
                $token->getLine()
            );
        } else {
            $this->stream->expect(Token::OPERATOR_TYPE, '[');
            $attr = $this->parseExpression();
            $this->stream->expect(Token::OPERATOR_TYPE, ']');
        }

        $args = array();
        if ($this->stream->consume(Token::OPERATOR_TYPE, '(')) {
            $call = true;
            while (!$this->stream->test(Token::OPERATOR_TYPE, ')')) {
                if (count($args)) {
                    $this->stream->expect(Token::OPERATOR_TYPE, ',');
                }
                $args[] = $this->parseExpression();
            }
            $this->stream->expect(Token::OPERATOR_TYPE, ')');
        }
        return new AttributeExpression($node, $attr, $args, $token->getLine());
    }

    protected function parseFilterExpression($node)
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $filters = array();
        while ($this->stream->test(Token::OPERATOR_TYPE, '|')) {
            $this->stream->next();
            $token = $this->stream->expect(Token::NAME_TYPE);

            $args = array();
            if ($this->stream->test(Token::OPERATOR_TYPE, '(')) {
                $this->stream->next();
                while (!$this->stream->test(Token::OPERATOR_TYPE, ')')) {
                    if (!empty($args)) {
                        $this->stream->expect(Token::OPERATOR_TYPE, ',');
                        if ($this->stream->test(Token::OPERATOR_TYPE, ')'))
                            break;
                    }
                    $args[] = $this->parseExpression();
                }
                $this->stream->expect(Token::OPERATOR_TYPE, ')');
            }

            $filters[] = array($token->getValue(), $args);

        }
        return new FilterExpression($node, $filters, false, $line);
    }
}

