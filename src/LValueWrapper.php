<?php

namespace Sterzik\Expression;

/*
 * Objects of this class represent a subexpression, which may be evaluated.
 * Calling of LValueWrapper::lvalue() will evaluate the corresponding expression
 * and return exactly the L-value representing the result. If the result was
 * not an l-value, it is automatically converted to a trivial L-value (with
 * only value() method implemented).
 */
class LValueWrapper
{
    private $expr;
    private $evaluator;
    private $variables;
    private $notLValue;

    /*
     * This object is constructed internally by the evaluation process.
     * There is no need to call the constructor directly and it even
     * should not be called. (the arguments may change in the future)
     */
    public function __construct($expr, $evaluator, $variables, $notLValue)
    {
        $this->expr = $expr;
        $this->evaluator = $evaluator;
        $this->variables = $variables;
        $this->notLValue = $notLValue;
    }

    /*
     * This function does the evaluation of the expression and
     * returns the corresponding lvalue. (even non-L-values are converted
     * to trivial L-values)
     */
    public function lvalue()
    {
        $result = $this->expr->evaluateExpression($this->variables, $this->evaluator);
        if (!is_a($result, __NAMESPACE__ . '\\LValue')) {
            if (is_a($result, __NAMESPACE__ . '\\LValueWrapper')) {
                $result = $result->lvalue();
            } else {
                $l = new LValueBuilder();

                $l->value(function () use ($result) {
                    return $result;
                });
                if (is_callable($this->notLValue)) {
                    $l->setDefaultCallback($this->notLValue);
                }
                $result = $l->getLValue();
            }
        }
        return $result;
    }

    /*
     * Any other calls are translated to calling the methods of the return value of $this->lvalue().
     */
    public function __call($function, $args)
    {
        return call_user_func_array([$this->lvalue(),$function], $args);
    }
}
