<?php

namespace Sterzik\Expression;

/*
 * This abstract class just shows what any L-value needs to support.
 * any L-value needs to support at least the value() method,
 * but it may define any other methods according to any scheme.
 *
 * Only evaluators are dealing with L-values, therefore evaluators
 * are responsible, which methods will be called.
 */
abstract class LValue
{
    /*
     * This method should return the value stored in the variable,
     * associated with that L-value
     */
    abstract public function value();

    /*
     * This function is just used to return itself. It has no meaning
     * at all. The reason to have this function implemented is just
     * for the reason that LValue and LValueWrapper have exactly the
     * same interface.
     */
    final public function lvalue()
    {
        return $this;
    }
}
