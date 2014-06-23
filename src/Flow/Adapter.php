<?php

namespace Flow;

interface Adapter
{
    public function isReadable($path);
    public function lastModified($path);
    public function getContents($path);
}

