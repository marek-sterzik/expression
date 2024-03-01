<?php

namespace Sterzik\Expression;

use Exception;

class VariableExpression extends Expression
{
    private $variable;
    public function __construct($v)
    {
        if (!is_string($v)) {
            throw new Exception("Invalid arguments when creating VariableExpression");
        }
        $this->variable = $v;
    }

    public function name()
    {
        return $this->variable;
    }

    public function evaluateExpression($variables, $evaluator)
    {
        return $evaluator->evalVariable($variables, $this->variable);
    }

    public function postprocessExpression($postprocessor)
    {
        return $postprocessor->postprocessVar($this);
    }

    public static function restoreExpression($data)
    {
        if (count($data) != 1) {
            return null;
        }
        $variable = array_shift($data);
        if (!is_string($variable)) {
            return null;
        }
        return new self($variable);
    }

    public function dumpExpression()
    {
        return [$this->variable];
    }
}
