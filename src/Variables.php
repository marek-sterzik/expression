<?php

namespace Sterzik\Expression;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class Variables implements ArrayAccess, Countable, IteratorAggregate
{
    private $vars;

    public function __construct($vars)
    {
        if (is_a($vars, __CLASS__)) {
            $vars = $vars->asArray();
        }
        if (!is_array($vars)) {
            $vars = [];
        }
        $this->vars = $vars;
    }

    public function asArray()
    {
        return $this->vars;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->vars);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->vars);
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->vars[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->vars[$offset]) ? $this->vars[$offset] : null;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->vars[$offset] = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->vars[$offset]);
    }
}
