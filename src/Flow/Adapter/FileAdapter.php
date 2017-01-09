<?php

namespace Flow\Adapter;

use Flow\Adapter;

final class FileAdapter implements Adapter
{
    private $directory;

    public function __construct($directory)
    {
        if (!($this->directory = realpath($directory)) || !is_dir($this->directory)) {
            throw new \RuntimeException(sprintf(
                'directory %s not found',
                $directory
            ));
        }
    }

    public function isReadable(string $path) : bool
    {
        return is_readable($this->directory . '/' . $path);
    }

    public function lastModified(string $path) : int
    {
        return filemtime($this->directory . '/' . $path);
    }

    public function getContents(string $path) : string
    {
        return file_get_contents($this->directory . '/' . $path);
    }
}

