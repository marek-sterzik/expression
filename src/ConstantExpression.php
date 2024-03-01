<?php

namespace Sterzik\Expression;

/*
 * This class represents a (sub)expression containing a constant
 */
class ConstantExpression extends Expression
{
    private $constant;


    /*
     * Constructor of this class should never be called directly.
     * Use Expression::create() instead.
     */
    public function __construct($c)
    {
        $this->constant = $c;
    }

    /*
     * Returns the value of the constant represented by this expression
     */
    public function value()
    {
        return $this->constant;
    }


    public function evaluateExpression($variables, $evaluator)
    {
        return $evaluator->evalConstant($variables, $this->constant);
    }

    public function postprocessExpression($postprocessor)
    {
        return $postprocessor->postprocessConst($this);
    }

    public static function restoreExpression($data)
    {
        if (count($data) != 1) {
            return null;
        }
        $constant = array_shift($data);
        return new self($constant);
    }

    public function dumpExpression()
    {
        return [$this->constant];
    }
}
