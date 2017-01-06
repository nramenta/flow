<?php

namespace Flow;

interface Adapter
{
    public function isReadable($path) : bool;
    public function lastModified($path) : int;
    public function getContents($path) : string;
}

