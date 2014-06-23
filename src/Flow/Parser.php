<?php

namespace Flow;

class Parser
{
    protected $stream;
    protected $extends;
    protected $blocks;
    protected $currentBlock;
    protected $tags;
    protected $inForLoop;
    protected $macros;
    protected $inMacro;
    protected $imports;
    protected $autoEscape;

    public function __construct(TokenStream $stream)
    {
        $this->stream  = $stream;
        $this->extends = null;
        $this->blocks  = array();

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
        return new Module(
            $this->extends, $this->imports, $this->blocks,
            $this->macros, $body
        );
    }

    protected function subparse($test = null, $next = false)
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $nodes = array();
        while (!$this->stream->isEOS()) {
            switch ($this->stream->getCurrentToken()->getType()) {
            case Token::TEXT:
                $token = $this->stream->next();
                $nodes[] = new Node\TextNode($token->getValue(), $token->getLine());
                break;
            case Token::BLOCK_START:
                $this->stream->next();
                $token = $this->stream->getCurrentToken();
                if ($token->getType() !== Token::NAME) {
                    throw new SyntaxError(
                        sprintf(
                            'unexpected "%s", expecting a valid tag',
                            str_replace("\n", '\n', $token->getValue())
                        ),
                        $token
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
                    if (is_array($test)) {
                        $expecting = '"' . implode('" or "', $test) . '"';
                    } elseif ($test) {
                        $expecting = '"' . $test . '"';
                    } else {
                        $expecting = 'a valid tag';
                    }
                    throw new SyntaxError(
                        sprintf(
                            'unexpected "%s", expecting %s',
                            str_replace("\n", '\n', $token->getValue()),
                            $expecting
                        ),
                        $token
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
                            'missing construct handler "%s"',
                            $token->getValue()
                        ),
                        $token
                    );
                }
                if (!is_null($node)) {
                    $nodes[] = $node;
                }
                break;

            case Token::OUTPUT_START:
                $token = $this->stream->next();
                $expr = $this->parseExpression();
                $autoEscape = $this->autoEscape[count($this->autoEscape) - 1];
                if ($autoEscape) {
                    $filters = array();
                    if ($expr instanceof FilterExpression) {
                        if (!$expr->isRaw()) $expr->setAutoEscape(true);
                    } else {
                        $expr = new Expression\FilterExpression(
                            $expr, $filters, true, $token->getLine()
                        );
                    }
                }
                $nodes[] = $this->parseIfModifier(
                    $token, new Node\OutputNode($expr, $token->getLine())
                );
                $this->stream->expect(Token::OUTPUT_END);
                break;
            default:
                throw new SyntaxError(
                    'parser ended up in unsupported state',
                    $this->stream->getCurrentToken()
                );
            }
        }
        return new NodeList($nodes, $line);
    }

    protected function parseName($expect = true, $match = null)
    {
        static $constants = array('true', 'false', 'null');
        static $operators = array('and', 'xor', 'or', 'not', 'in');

        if ($this->stream->test(Token::CONSTANT, $constants)) {
            return $this->stream->expect(Token::CONSTANT, $match);
        } elseif ($this->stream->test(Token::OPERATOR, $operators)) {
            return $this->stream->expect(Token::OPERATOR, $match);
        } elseif ($expect or $this->stream->test(Token::NAME)) {
            return $this->stream->expect(Token::NAME, $match);
        }
    }

    protected function parseIf($token)
    {
        $line = $token->getLine();
        $expr = $this->parseExpression();
        $this->stream->expect(Token::BLOCK_END);
        $body = $this->subparse(array('elseif', 'else', 'endif'));
        $tests = array(array($expr, $body));
        $else = null;

        $end = false;
        while (!$end) {
            switch ($this->stream->next()->getValue()) {
            case 'elseif':
                $expr = $this->parseExpression();
                $this->stream->expect(Token::BLOCK_END);
                $body = $this->subparse(array('elseif', 'else', 'endif'));
                $tests[] = array($expr, $body);
                break;
            case 'else':
                $this->stream->expect(Token::BLOCK_END);
                $else = $this->subparse(array('endif'));
                break;
            case 'endif':
                $this->stream->expect(Token::BLOCK_END);
                $end = true;
                break;
            default:
                throw new SyntaxError('malformed if statement', $token);
                break;
            }
        }
        return new Node\IfNode($tests, $else, $line);
    }

    protected function parseIfModifier($token, $node)
    {
        static $modifiers = array('if', 'unless');

        if ($this->stream->test($modifiers)) {
            $statement = $this->stream->expect($modifiers)->getValue();
            $test_expr = $this->parseExpression();
            if ($statement == 'if') {
                $node = new Node\IfNode(
                    array(array($test_expr, $node)), null, $token->getLine()
                );
            } elseif ($statement == 'unless') {
                $node = new Node\IfNode(
                    array(array(
                        new Expression\NotExpression($test_expr, $token->getLine()), $node
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
        $value = $this->parseName()->getValue();
        if ($this->stream->consume(Token::OPERATOR, ',')) {
            $key = $value;
            $value = $this->parseName()->getValue();
        }
        $this->stream->expect(Token::OPERATOR, 'in');
        $seq = $this->parseExpression();
        $this->stream->expect(Token::BLOCK_END);
        $body = $this->subparse(array('else', 'endfor'));
        $this->inForLoop--;
        if ($this->stream->getCurrentToken()->getValue() == 'else') {
            $this->stream->next();
            $this->stream->expect(Token::BLOCK_END);
            $else = $this->subparse('endfor');
            if ($this->stream->getCurrentToken()->getValue() != 'endfor') {
                throw new SyntaxError('malformed for statement', $token);
            }
        } elseif ($this->stream->getCurrentToken()->getValue() == 'endfor') {
            $else = null;
        } else {
            throw new SyntaxError('malformed for statement', $token);
        }
        $this->stream->next();
        $this->stream->expect(Token::BLOCK_END);
        return new Node\ForNode($seq, $key, $value, $body, $else, $line);
    }

    protected function parseBreak($token)
    {
        if (!$this->inForLoop) {
            throw new SyntaxError('unexpected break, not in for loop', $token);
        }
        $node = $this->parseIfModifier(
            $token, new Node\BreakNode($token->getLine())
        );
        $this->stream->expect(Token::BLOCK_END);
        return $node;
    }

    protected function parseContinue($token)
    {
        if (!$this->inForLoop) {
            throw new SyntaxError(
                'unexpected continue, not in for loop', $token
            );
        }
        $node = $this->parseIfModifier(
            $token, new Node\ContinueNode($token->getLine())
        );
        $this->stream->expect(Token::BLOCK_END);
        return $node;
    }

    protected function parseExtends($token)
    {
        if (!is_null($this->extends)) {
            throw new SyntaxError('multiple extends tags', $token);
        }

        if (!empty($this->currentBlock)) {
            throw new SyntaxError(
                'cannot declare extends inside blocks', $token
            );
        }

        if ($this->inMacro) {
            throw new SyntaxError(
                'cannot declare extends inside macros', $token
            );
        }

        $parent = $this->parseExpression();
        $params = null;

        if ($this->stream->consume(Token::NAME, 'with')) {
            $this->stream->expect(Token::OPERATOR, '[');
            $params = $this->parseArrayExpression();
            $this->stream->expect(Token::OPERATOR, ']');
        }

        $this->extends = $this->parseIfModifier(
            $token, new Node\ExtendsNode($parent, $params, $token->getLine())
        );

        $this->stream->expect(Token::BLOCK_END);
        return null;
    }

    protected function parseSet($token)
    {
        $attrs = array();
        $name = $this->parseName()->getValue();
        while (!$this->stream->test(Token::OPERATOR, '=') &&
            !$this->stream->test(Token::BLOCK_END)
        ) {
            if ($this->stream->consume(Token::OPERATOR, '.')) {
                $attrs[] = $this->parseName()->getValue();
            } else {
                $this->stream->expect(Token::OPERATOR, '[');
                $attrs[] = $this->parseExpression();
                $this->stream->expect(Token::OPERATOR, ']');
            }
        }
        if ($this->stream->consume(Token::OPERATOR, '=')) {
            $value = $this->parseExpression();
            $node = $this->parseIfModifier(
                $token, new Node\SetNode($name, $attrs, $value, $token->getLine())
            );
            $this->stream->expect(Token::BLOCK_END);
        } else {
            $this->stream->expect(Token::BLOCK_END);
            $body = $this->subparse('endset');
            if ($this->stream->next()->getValue() != 'endset') {
                throw new SyntaxError('malformed set statement', $token);
            }
            $this->stream->expect(Token::BLOCK_END);
            $node = new Node\SetNode($name, $attrs, $body, $token->getLine());
        }
        return $node;
    }

    protected function parseBlock($token)
    {
        if ($this->inMacro) {
            throw new SyntaxError(
                'cannot declare blocks inside macros', $token
            );
        }
        $name = $this->parseName()->getValue();
        if (isset($this->blocks[$name])) {
            throw new SyntaxError(
                sprintf('block "%s" already defined', $name),
                $token
            );
        }
        array_push($this->currentBlock, $name);
        if ($this->stream->consume(Token::BLOCK_END)) {
            $body = $this->subparse('endblock');
            if ($this->stream->next()->getValue() != 'endblock') {
                throw new SyntaxError('malformed block statement', $token);
            }
            $this->parseName(false, $name);
        } else {
            $expr = $this->parseExpression();
            $autoEscape = $this->autoEscape[count($this->autoEscape) - 1];
            if ($autoEscape) {
                $filters = array();
                if ($expr instanceof FilterExpression) {
                    if (!$expr->isRaw()) $expr->setAutoEscape(true);
                } else {
                    $expr = new Expression\FilterExpression(
                        $expr, $filters, true, $token->getLine()
                    );
                }
            }
            $body = new Node\OutputNode($expr, $token->getLine());
        }
        $this->stream->expect(Token::BLOCK_END);
        array_pop($this->currentBlock);
        $this->blocks[$name] = new Node\BlockNode($name, $body, $token->getLine());
        return new Node\BlockDisplayNode($name, $token->getLine());
    }

    protected function parseParent($token)
    {
        if ($this->inMacro) {
            throw new SyntaxError(
                'cannot call parent block inside macros', $token
            );
        }

        if (empty($this->currentBlock)) {
            throw new SyntaxError('parent must be inside a block', $token);
        }

        $node = $this->parseIfModifier(
            $token,
            new Node\ParentNode($this->currentBlock[count($this->currentBlock) - 1],
            $token->getLine())
        );
        $this->stream->expect(Token::BLOCK_END);
        return $node;
    }

    protected function parseAutoEscape($token)
    {
        $autoEscape = $this->stream->expect(array('on', 'off'))->getValue();
        $this->stream->expect(Token::BLOCK_END);
        array_push($this->autoEscape, $autoEscape == 'on' ? true : false);
        return null;
    }

    protected function parseEndAutoEscape($token)
    {
        if (empty($this->autoEscape)) {
            throw new SyntaxError('unmatched endautoescape tag', $token);
        }
        array_pop($this->autoEscape);
        $this->stream->expect(Token::BLOCK_END);
        return null;
    }

    protected function parseMacro($token)
    {
        if (!empty($this->currentBlock)) {
            throw new SyntaxError(
                'cannot declare macros inside blocks', $token
            );
        }

        if ($this->inMacro) {
            throw new SyntaxError(
                'cannot declare macros inside another macro', $token
            );
        }

        $this->inMacro = true;
        $name = $this->parseName()->getValue();
        if (isset($this->macros[$name])) {
            throw new SyntaxError(
                sprintf('macro "%s" already defined', $name),
                $token
            );
        }
        $args = array();
        if ($this->stream->consume(Token::OPERATOR, '(')) {
            while (!$this->stream->test(Token::OPERATOR, ')')) {
                if (!empty($args)) {
                    $this->stream->expect(Token::OPERATOR, ',');
                    if ($this->stream->test(Token::OPERATOR, ')'))
                        break;
                }
                $key = $this->parseName()->getValue();
                if ($this->stream->consume(Token::OPERATOR, '=')) {
                    $val = $this->parseLiteralExpression();
                } else {
                    $val = new Expression\ConstantExpression(null, $token->getLine());
                }
                $args[$key] = $val;
            }
            $this->stream->expect(Token::OPERATOR, ')');
        }
        $this->stream->expect(Token::BLOCK_END);
        $body = $this->subparse('endmacro');
        if ($this->stream->next()->getValue() != 'endmacro') {
            throw new SyntaxError('malformed macro statement', $token);
        }
        $this->stream->consume(Token::NAME, $name);
        $this->stream->expect(Token::BLOCK_END);
        $this->macros[$name] = new Node\MacroNode(
            $name, $args, $body, $token->getLine()
        );
        $this->inMacro = false;
    }

    protected function parseImport($token)
    {
        $import = $this->parseExpression();
        $this->stream->expect(Token::NAME, 'as');
        $module = $this->parseName()->getValue();
        $this->stream->expect(Token::BLOCK_END);
        $this->imports[$module] = new Node\ImportNode(
            $module, $import, $token->getLine()
        );
    }

    protected function parseInclude($token)
    {
        $include = $this->parseExpression();
        $params = null;

        if ($this->stream->consume(Token::NAME, 'with')) {
            $this->stream->expect(Token::OPERATOR, '[');
            $params = $this->parseArrayExpression();
            $this->stream->expect(Token::OPERATOR, ']');
        }

        $node = $this->parseIfModifier(
            $token, new Node\IncludeNode($include, $params, $token->getLine())
        );

        $this->stream->expect(Token::BLOCK_END);
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
        while ($this->stream->consume(Token::OPERATOR, '?')) {
            $expr2 = $this->parseOrExpression();
            $this->stream->expect(Token::OPERATOR, ':');
            $expr3 = $this->parseConditionalExpression();
            $expr1 = new Expression\ConditionalExpression($expr1, $expr2, $expr3, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $expr1;
    }

    protected function parseXorExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseOrExpression();
        while ($this->stream->consume(Token::OPERATOR, 'xor')) {
            $right = $this->parseOrExpression();
            $left = new Expression\XorExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseOrExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseAndExpression();
        while ($this->stream->consume(Token::OPERATOR, 'or')) {
            $right = $this->parseAndExpression();
            $left = new Expression\OrExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseAndExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseNotExpression();
        while ($this->stream->consume(Token::OPERATOR, 'and')) {
            $right = $this->parseNotExpression();
            $left = new Expression\AndExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseNotExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        if ($this->stream->consume(Token::OPERATOR, 'not')) {
            $node = $this->parseNotExpression();
            return new Expression\NotExpression($node, $line);
        }
        return $this->parseInclusionExpression();
    }

    protected function parseInclusionExpression()
    {
        static $operators = array('not', 'in');

        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseCompareExpression();
        while ($this->stream->test(Token::OPERATOR, $operators)) {
            if ($this->stream->consume(Token::OPERATOR, 'not')) {
                $this->stream->expect(Token::OPERATOR, 'in');
                $right = $this->parseCompareExpression();
                $left = new Expression\NotExpression(
                    new Expression\InclusionExpression($left, $right, $line), $line
                );
            } else {
                $this->stream->expect(Token::OPERATOR, 'in');
                $right = $this->parseCompareExpression();
                $left = new Expression\InclusionExpression($left, $right, $line);
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
        while ($this->stream->test(Token::OPERATOR, $operators)) {
            $ops[] = array(
                $this->stream->next()->getValue(),
                $this->parseAddExpression()
            );
        }

        if (empty($ops)) {
            return $expr;
        }
        return new Expression\CompareExpression($expr, $ops, $line);
    }

    protected function parseConcatExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseJoinExpression();
        while ($this->stream->consume(Token::OPERATOR, '~')) {
            $right = $this->parseJoinExpression();
            $left = new Expression\ConcatExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseJoinExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseAddExpression();
        while ($this->stream->consume(Token::OPERATOR, '..')) {
            $right = $this->parseAddExpression();
            $left = new Expression\JoinExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseAddExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseSubExpression();
        while ($this->stream->consume(Token::OPERATOR, '+')) {
            $right = $this->parseSubExpression();
            $left = new Expression\AddExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseSubExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseMulExpression();
        while ($this->stream->consume(Token::OPERATOR, '-')) {
            $right = $this->parseMulExpression();
            $left = new Expression\SubExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseMulExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseDivExpression();
        while ($this->stream->consume(Token::OPERATOR, '*')) {
            $right = $this->parseDivExpression();
            $left = new Expression\MulExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseDivExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseModExpression();
        while ($this->stream->consume(Token::OPERATOR, '/')) {
            $right = $this->parseModExpression();
            $left = new Expression\DivExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseModExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $left = $this->parseUnaryExpression();
        while ($this->stream->consume(Token::OPERATOR, '%')) {
            $right = $this->parseUnaryExpression();
            $left = new Expression\ModExpression($left, $right, $line);
            $line = $this->stream->getCurrentToken()->getLine();
        }
        return $left;
    }

    protected function parseUnaryExpression()
    {
        if ($this->stream->test(Token::OPERATOR, array('-', '+'))) {
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
        return new Expression\NegExpression($node, $token->getLine());
    }

    protected function parsePosExpression()
    {
        $token = $this->stream->next();
        $node = $this->parseUnaryExpression();
        return new Expression\PosExpression($node, $token->getLine());
    }

    protected function parsePrimaryExpression()
    {
        $token = $this->stream->getCurrentToken();
        switch ($token->getType()) {
        case Token::CONSTANT:
        case Token::NUMBER:
        case Token::STRING:
            $node = $this->parseLiteralExpression();
            break;
        case Token::NAME:
            $this->stream->next();
            $node = new Expression\NameExpression($token->getValue(), $token->getLine());
            if ($this->stream->test(Token::OPERATOR, '(')) {
                $node = $this->parseFunctionCallExpression($node);
            }
            break;
        case Token::CONSTANT:
        case Token::OPERATOR:
            if (($name = $this->parseName(false)) !== null) {
                $node = new Expression\NameExpression(
                    $name->getValue(), $token->getLine()
                );
                break;
            }
        default:
            if ($this->stream->consume(Token::OPERATOR, '@')) {
                $node = new Expression\FilterExpression(
                    $this->parseMacroExpression($token), array('raw'), false,
                    $token->getLine()
                );
            } elseif ($this->stream->consume(Token::OPERATOR, '[')) {
                $node = $this->parseArrayExpression();
                $this->stream->expect(Token::OPERATOR, ']');
            } elseif ($this->stream->consume(Token::OPERATOR, '(')) {
                $node = $this->parseExpression();
                $this->stream->expect(Token::OPERATOR, ')');
            } else {
                throw new SyntaxError(
                    sprintf(
                        'unexpected "%s", expecting an expression',
                        str_replace("\n", '\n', $token->getValue())
                    ),
                    $token
                );
            }
        }
        return $this->parsePostfixExpression($node);
    }

    protected function parseLiteralExpression()
    {
        $token = $this->stream->getCurrentToken();
        switch ($token->getType()) {
        case Token::CONSTANT:
            $this->stream->next();
            switch ($token->getValue()) {
            case 'true':
                $node = new Expression\ConstantExpression(true, $token->getLine());
                break;
            case 'false':
                $node = new Expression\ConstantExpression(false, $token->getLine());
                break;
            case 'null':
                $node = new Expression\ConstantExpression(null, $token->getLine());
                break;
            }
            break;
        case Token::NUMBER:
            $this->stream->next();
            if (preg_match('/\./', $token->getValue())) {
                $node = new Expression\ConstantExpression(
                    floatval($token->getValue()), $token->getLine()
                );
            } else {
                $node = new Expression\ConstantExpression(
                    intval($token->getValue()), $token->getLine()
                );
            }
            break;
        case Token::STRING:
            $this->stream->next();
            $node = new Expression\StringExpression(
                strval($token->getValue()), $token->getLine()
            );
            break;
        default:
            throw new SyntaxError(
                sprintf(
                    'unexpected "%s", expecting an expression',
                    str_replace("\n", '\n', $token->getValue())
                ),
                $token
            );
        }
        return $this->parsePostfixExpression($node);
    }

    protected function parseFunctionCallExpression($node)
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $this->stream->expect(Token::OPERATOR, '(');
        $args = array();
        while (!$this->stream->test(Token::OPERATOR, ')')) {
            if (!empty($args)) {
                $this->stream->expect(Token::OPERATOR, ',');
                if ($this->stream->test(Token::OPERATOR, ')')) break;
            }
            $args[] = $this->parseExpression();
        }
        $this->stream->expect(Token::OPERATOR, ')');
        return new Expression\FunctionCallExpression($node, $args, $line);
    }

    protected function parseMacroExpression($token)
    {
        static $constants = array('true', 'false', 'null');
        static $operators = array('and', 'xor', 'or', 'not', 'in');

        $module = null;
        $name = $this->parseName()->getValue();
        if ($this->stream->consume(Token::OPERATOR, '.')) {
            $module = $name;
            $name = $this->parseName()->getValue();
        }
        $args = array();

        if ($this->stream->consume(Token::OPERATOR, '(')) {
            while (!$this->stream->test(Token::OPERATOR, ')')) {
                if (!empty($args)) {
                    $this->stream->expect(Token::OPERATOR, ',');
                    if ($this->stream->test(Token::OPERATOR, ')'))
                        break;
                }
                if (($this->stream->test(Token::NAME) ||
                    $this->stream->test(Token::CONSTANT, $constants) ||
                    $this->stream->test(Token::OPERATOR, $operators)) &&
                    $this->stream->look()->test(Token::OPERATOR, '=')
                ) {
                    $key = $this->parseName()->getValue();
                    $this->stream->expect(Token::OPERATOR, '=');
                    $val = $this->parseExpression();
                    $args[$key] = $val;
                } else {
                    $args[] = $this->parseExpression();
                }
            }
            $this->stream->expect(Token::OPERATOR, ')');
        }
        return new Expression\MacroExpression($module, $name, $args, $token->getLine());
    }

    protected function parseArrayExpression()
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $elements = array();
        do {
            $token = $this->stream->getCurrentToken();
            if ($token->test(Token::OPERATOR, ']')) break;
            if ($token->test(Token::NAME) ||
                $token->test(Token::STRING) ||
                $token->test(Token::NUMBER)
            ) {
                if ($token->test(Token::NAME) ||
                    $token->test(Token::STRING)
                ) {
                    $key = new Expression\ConstantExpression(
                        strval($token->getValue()), $line
                    );
                } else {
                    if (preg_match('/\./', $token->getValue())) {
                        $key = new Expression\ConstantExpression(
                            floatval($token->getValue()), $line
                        );
                    } else {
                        $key = new Expression\ConstantExpression(
                            intval($token->getValue()), $line
                        );
                    }
                }
                $this->stream->next();
                if ($this->stream->consume(Token::OPERATOR, array('=>'))) {
                    $element = $this->parseExpression();
                    $elements[] = array($key, $element);
                } else {
                    $elements[] = $key;
                }
            } else {
                $elements[] = $this->parseExpression();
            }
            $this->stream->consume(Token::OPERATOR, ',');
        } while (!$this->stream->test(Token::OPERATOR, ']'));
        return new Expression\ArrayExpression($elements, $line);
    }

    protected function parsePostfixExpression($node)
    {
        $stop = false;
        while (!$stop &&
            $this->stream->getCurrentToken()->getType() == Token::OPERATOR
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
        if ($this->stream->consume(Token::OPERATOR, '.')) {
            $attr = new Expression\ConstantExpression(
                $this->parseName()->getValue(),
                $token->getLine()
            );
        } else {
            $this->stream->expect(Token::OPERATOR, '[');
            $attr = $this->parseExpression();
            $this->stream->expect(Token::OPERATOR, ']');
        }

        $args = false;
        if ($this->stream->consume(Token::OPERATOR, '(')) {
            $args = array();
            while (!$this->stream->test(Token::OPERATOR, ')')) {
                if (count($args)) {
                    $this->stream->expect(Token::OPERATOR, ',');
                }
                $args[] = $this->parseExpression();
            }
            $this->stream->expect(Token::OPERATOR, ')');
        }
        return new Expression\AttributeExpression($node, $attr, $args, $token->getLine());
    }

    protected function parseFilterExpression($node)
    {
        $line = $this->stream->getCurrentToken()->getLine();
        $filters = array();
        while ($this->stream->test(Token::OPERATOR, '|')) {
            $this->stream->next();
            $token = $this->stream->expect(Token::NAME);

            $args = array();
            if ($this->stream->test(Token::OPERATOR, '(')) {
                $this->stream->next();
                while (!$this->stream->test(Token::OPERATOR, ')')) {
                    if (!empty($args)) {
                        $this->stream->expect(Token::OPERATOR, ',');
                        if ($this->stream->test(Token::OPERATOR, ')'))
                            break;
                    }
                    $args[] = $this->parseExpression();
                }
                $this->stream->expect(Token::OPERATOR, ')');
            }

            $filters[] = array($token->getValue(), $args);

        }
        return new Expression\FilterExpression($node, $filters, false, $line);
    }
}

