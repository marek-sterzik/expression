<?php

namespace Sterzik\Expression;

/**
 * This class is used to create custom L-values.
 * Methods of the expected L-value may be defined by
 * calling the setCallback() method or by calling
 * directly the method, which is equivalent to calling
 * setCallback with first argument set to the method name.
 *
 * For example:
 *
 *  $lvalueBuilder->setCallback("value",function(){...});
 *
 * is equivalent to:
 *
 * $lvalueBuilder->value(function(){...});
 *
 * @method mixed value(callable $callback)
 * @method mixed assign(callable $callback)
 */
class LValueBuilder
{
    private $callbacks;
    private $defaultCallback;

    /*
     * Constructs a new L-value builder
     */
    public function __construct()
    {
        $this->callbacks = [];
        $this->defaultCallback = null;
    }

    /*
     * Sets an L-value method
     * For each L-value, at least the method "value" needs to be defined
     */
    public function setCallback($operation, $callback, $addFnName = false)
    {
        if (is_callable($callback) && !$addFnName) {
            $callback = function ($fnName, ...$arguments) use ($callback) {
                return $callback(...$arguments);
            };
        }
        if ($operation === null) {
            $this->defaultCallback = ($callback !== null) ? $callback : false;
        } elseif (is_string($operation)) {
            $this->callbacks[$operation] = $callback;
        }
    }

    /*
     * Sets the default callback (which will be called when no callback for the called method was defined
     */
    public function setDefaultCallback($callback, $addFnName = true)
    {
        return $this->setCallback(null, $callback, $addFnName);
    }

    /*
     * Calling any other methods is just a shortcut to quickly call setCallback.
     */
    public function __call($function, $arguments)
    {
        return $this->setCallback($function, ...$arguments);
    }

    /*
     * Gets the L-value, when all callbacks were correctly defined
     */
    public function getLValue()
    {
        return new BuiltLValue($this->callbacks, $this->defaultCallback);
    }
}
