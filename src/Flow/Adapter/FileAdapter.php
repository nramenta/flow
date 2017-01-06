<?php

namespace Flow\Adapter;

use Flow\Adapter;

final class FileAdapter implements Adapter
{
    private $source;

    public function __construct($source)
    {
        if (!($this->source = realpath($source)) || !is_dir($this->source)) {
            throw new \RuntimeException(sprintf(
                'source directory %s not found',
                $source
            ));
        }
    }

    public function isReadable($path) : bool
    {
        return is_readable($this->source. '/' . $path);
    }

    public function lastModified($path) : int
    {
        return filemtime($this->source. '/' . $path);
    }

    public function getContents($path) : string
    {
        return file_get_contents($this->source . '/' . $path);
    }
}

