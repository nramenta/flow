<?php

namespace Flow\Helper;

final class ContextIterator implements \Iterator
{
    private $sequence;

    public function __construct($sequence, $parent)
    {
        if ($sequence instanceof \Traversable) {
            $this->length = ($sequence instanceof \Countable) ?
                count($sequence) : iterator_count($sequence);
            $this->sequence = $sequence;
        } elseif (is_array($sequence)) {
            $this->length = count($sequence);
            $this->sequence = new \ArrayIterator($sequence);
        } else {
            $this->length = 0;
            $this->sequence = new \ArrayIterator;
        }
        $this->parent = $parent;
    }

    public function rewind()
    {
        $this->sequence->rewind();

        $this->index = 0;
        $this->count = $this->index + 1;
        $this->first = $this->count == 1;
        $this->last  = $this->count == $this->length;
    }

    public function key()
    {
        return $this->sequence->key();
    }

    public function valid()
    {
        return $this->sequence->valid();
    }

    public function next()
    {
        $this->sequence->next();

        $this->index += 1;
        $this->count  = $this->index + 1;
        $this->first  = $this->count == 1;
        $this->last   = $this->count == $this->length;
    }

    public function current()
    {
        return $this->sequence->current();
    }
}

