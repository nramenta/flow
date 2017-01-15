<?php

namespace Flow;

use PHPUnit_Framework_TestCase;

use Flow\Loader;
use Flow\Adapter\FileAdapter;

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
        $paths = [];

        $actual = realpath(__DIR__ . '/actual');

        $dir = new \DirectoryIterator($actual);

        foreach ($dir as $file) {
            if ($file->isFile()) {
                $outputFile = realpath(__DIR__ . '/output/' . $file->getBasename());
                if (is_readable($outputFile)) {
                    $paths[] = [
                        trim(substr($file->getPathname(), strlen($actual)), '/'),
                        $outputFile,
                    ];
                }
            }
        }

        return $paths;
    }

    /**
     * @dataProvider outputProvider
     */
    public function testOutput($actual, $expected)
    {
        $expected = file_get_contents($expected);
        $template = $this->flow->load($actual);
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

