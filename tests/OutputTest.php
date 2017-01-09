<?php

require __DIR__ . '/../src/Flow/Loader.php';

use Flow\Loader;
use Flow\Helper;
use Flow\Adapter\FileAdapter;

Loader::autoload();

class OutputTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $options = [
            'source' => __DIR__ . '/actual',
            'target' => __DIR__ . '/cache',
            'mode'   => Loader::RECOMPILE_ALWAYS,
        ];

        $source = new FileAdapter($options['source']);

        $this->flow = new Loader($options, $source);
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
            'if',
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
        $expected = file_get_contents(__DIR__ . "/output/$data.html");
        $template = $this->flow->load("$data.html");
        $actual = $template->render();
        $this->assertEquals($expected, $actual);
    }

    public function testLoadTemplateFromAbsolutePath()
    {
        $template = $this->flow->load('includes/absolute.html');
        $actual = $template->render();
        $this->assertContains('absolute', $actual);
    }

    public function testLoadTemplateFromRelativePath()
    {
        $template = $this->flow->load('includes/relative.html');
        $actual = $template->render();
        $this->assertContains('relative', $actual);
    }
}

