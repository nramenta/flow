<?php

namespace Flow\Adapter;

use Flow\Adapter;

final class FileAdapter implements Adapter
{
    private $root;

    public function __construct($root)
    {
        if (!($this->root = realpath($root)) || !is_dir($this->root)) {
            throw new \RuntimeException(sprintf(
                'directory %s not found',
                $root
            ));
        }
    }

    public function isReadable(string $path) : bool
    {
        return is_readable($this->getStreamUrl($path));
    }

    public function lastModified(string $path) : int
    {
        return filemtime($this->getStreamUrl($path));
    }

    public function getContents(string $path) : string
    {
        return file_get_contents($this->getStreamUrl($path));
    }

    public function putContents(string $path, string $contents) : int
    {
        return file_put_contents($this->getStreamUrl($path), $contents);
    }

    public function getStreamUrl(string $path) : string
    {
        return 'file://' . $this->root . '/' . $path;
    }
}

