<?php

namespace Sterzik\Expression;

/*
 * This is a subclass of the abstract L-value, which is generated by the
 * LValueBuilder class. No need to use this class directly.
 */

class BuiltLValue extends LValue
{
    private $callbacks;
    private $defaultCallback;

    /*
     * Constructor of the class, it takes as parameters
     * all necessary callbacks. No need to construct this class
     * directly. The class should be created only by the
     * LValueBuilder
     */
    public function __construct($callbacks, $defaultCallback)
    {
        $this->callbacks = $callbacks;
        $this->defaultCallback = $defaultCallback;
    }

    /*
     * the value() method is implemented in the same way as
     * any other L-value methods.
     */
    public function value()
    {
        return $this->__call("value", []);
    }

    /*
     * Any method called is proceeded by the given callbacks.
     */
    public function __call($function, $arguments)
    {
        if (isset($this->callbacks[$function])) {
            $fn = $this->callbacks[$function];
        } elseif ($this->defaultCallback !== null) {
            $fn = $this->defaultCallback;
        } else {
            $fn = null;
        }
        if (!is_callable($fn)) {
            return null;
        }
        return $fn($function, ...$arguments);
    }
}
