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

        $source = new FileAdapter(__DIR__ . '/actual');

        $target = new FileAdapter(__DIR__ . '/cache');

        $this->flow = new Loader(Loader::RECOMPILE_ALWAYS, $source, $target);
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
            'macro_with',
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
        $this->assertEquals($expected, $actual, get_class($template));
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

    public function testLoadTemplateOutsideSource()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('../outside.html resolves to a path outside source');

        $template = $this->flow->load('../outside.html');
        $actual = $template->render();
    }
}

