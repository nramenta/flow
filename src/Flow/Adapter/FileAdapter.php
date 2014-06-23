<?php

namespace Flow\Adapter;

use Flow\Adapter;

class FileAdapter implements Adapter
{
    protected $source;

    public function __construct($source)
    {
        if (!($this->source = realpath($source)) || !is_dir($this->source)) {
            throw new \RuntimeException(sprintf(
                'source directory %s not found',
                $source
            ));
        }
    }

    public function isReadable($path)
    {
        return is_readable($this->source. '/' . $path);
    }

    public function lastModified($path)
    {
        return filemtime($this->source. '/' . $path);
    }

    public function getContents($path)
    {
        return file_get_contents($this->source . '/' . $path);
    }
}

