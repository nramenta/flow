<?php

namespace Flow;

interface Adapter
{
    public function isReadable(string $path) : bool;
    public function lastModified(string $path) : int;
    public function getContents(string $path) : string;
}

