<?php

namespace Flow\Helper;

final class RangeIterator implements \Iterator
{
    private $lower;
    private $upper;
    private $step;
    private $current;

    public function __construct($lower, $upper, $step = 1)
    {
        $this->lower = $lower;
        $this->upper = $upper;
        $this->step = $step;
    }

    public function length()
    {
        return \abs($this->upper - $this->lower) / \abs($this->step);
    }

    public function includes($n)
    {
        if ($this->upper >= $this->lower) {
            return $n >= $this->lower && $n <= $this->upper;
        } else {
            return $n <= $this->lower && $n >= $this->upper;
        }
    }

    public function random($seed = null)
    {
        if (isset($seed)) mt_srand($seed);
        return $this->upper >= $this->lower ?
            mt_rand($this->lower, $this->upper) :
            mt_rand($this->upper, $this->lower);
    }

    public function rewind()
    {
        $this->current = $this->lower;
    }

    public function key()
    {
        return $this->current;
    }

    public function valid()
    {
        if ($this->upper >= $this->lower) {
            return $this->current >= $this->lower &&
                $this->current <= $this->upper;
        } else {
            return $this->current <= $this->lower &&
                $this->current >= $this->upper;
        }
    }

    public function next()
    {
        $this->current += $this->step;
        return $this;
    }

    public function current()
    {
        return $this->current;
    }
}

