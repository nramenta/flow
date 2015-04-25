<?php

require __DIR__ . '/../src/Flow/Loader.php';

use Flow\Loader;
use Flow\Helper;

Loader::autoload();

class OutputTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->flow = new Loader([
            'source' => __DIR__ . '/actual',
            'target' => __DIR__ . '/cache',
        ]);
    }

    public function outputProvider()
    {
        $tests = [
            'and',
            'add',
            'array',
            'block',
            'comparison',
            'concat',
            'conditional',
            'div',
            'for',
            'for_else',
            'if',
            'if_else',
            'if_inline',
            'in',
            'include',
            'join',
            'logical',
            'macro',
            'mul',
            'or',
            'output',
            'set',
            'sub',
            'unless',
            'xor',
        ];
        return array_map(function($item) {
            return [$item];
        }, $tests);
    }

    /**
     * @dataProvider outputProvider
     */
    public function testOutput($data)
    {
        $expected = __DIR__ . "/expected/$data.html";
        $this->assertTrue($this->flow->isValid("$data.html"));
        $template = $this->flow->load("$data.html");
        $this->assertEquals(file_get_contents($expected), $template->render());
    }
}

