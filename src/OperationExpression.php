<?php

namespace Sterzik\Expression;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Exception;

/*
 * This class represents a (sub)expression of an operation.
 * Each operation has its name (represented by any string) and
 * an ordered list of arguments. While it is quite common, that
 * one type of operation will have just a single constant number of arguments,
 * some operations may have variable number of of arguments.
 */
class OperationExpression extends Expression implements ArrayAccess, Countable, IteratorAggregate
{
    private $op;
    private $subs;

    /*
     * Constructor of this class should never be called directly.
     * Use Expression::create() instead.
     */
    public function __construct($op, ...$args)
    {
        if (!is_string($op)) {
            throw new Exception("Invalid arguments when creating OperationExpression");
        }
        $this->op = $op;
        $this->subs = $this->findSubs($args);
    }

    /*
     * Returns the operation identifier.
     */
    public function op()
    {
        return $this->op;
    }

    /*
     * Returns the list of arguments (which are expressions as well)
     */
    public function arguments()
    {
        return $this->subs;
    }

    /*
     * The countable interface: count() returns the number of arguments
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->subs);
    }

    /*
     * Iteration over this object iterates over the arguments of that operation
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new ArrayIterator($this->subs);
    }

    /*
     * The ArrayAccess interface: indexing this object is the same as indexing the arguments.
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->subs[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->subs[$offset]) ? $this->subs[$offset] : null;
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
    }

    private function findSubs($args)
    {
        $subs = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $subs = array_merge($subs, $this->findSubs($arg));
            } elseif (is_a($arg, __NAMESPACE__ . '\\Expression')) {
                $subs[] = $arg;
            } else {
                throw new Exception("Invalid arguments when creating OperationExpression");
            }
        }
        return $subs;
    }

    public static function restoreExpression($data)
    {
        if (count($data) == 0) {
            return null;
        }
        $op = array_shift($data);
        if (!is_string($op)) {
            return null;
        }
        $subs = [];
        foreach ($data as $d) {
            $x = Expression::restore($d);
            if ($x === null) {
                return null;
            }
            $subs[] = $x;
        }
        return new self($op, $subs);
    }

    public function dumpExpression()
    {
        $data = [$this->op];
        foreach ($this->subs as $sub) {
            $data[] = $sub->dump();
        }
        return $data;
    }

    public function evaluateExpression($variables, $evaluator)
    {
        return $evaluator->evalOp($variables, $this->op, $this->subs);
    }

    public function postprocessExpression($postprocessor)
    {
        foreach ($this->subs as &$sub) {
            $r = $sub->postprocessExpression($postprocessor);
            if ($r === false) {
                return false;
            }
            $sub = $r;
        }
        return $postprocessor->postprocessOp($this->op, $this);
    }
}
