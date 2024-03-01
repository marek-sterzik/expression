<?php

namespace Sterzik\Expression;

use ReflectionFunction;

/*
 * This class defines an evaluator of expressions. It is not intended to be subclassed.
 * Any evaluator should be created by calling the methods of this class.
 */

final class Evaluator
{
    use Priv\DefaultStorageTrait;

    private $ops;
    private static $presetClass = "EvaluatorPreset";

    public function __construct()
    {
        $this->ops = [];
    }

    /*
     * Defines an operation handler (with handling l-values and evaluations)
     */
    public function defOpEx($op, $function, $passOpName = false)
    {
        if (is_callable($function)) {
            $index = $this->indexFunction($function, !$passOpName);
            $this->doDefOp("op", $op, $this->createExFunction($function, !$passOpName, false), $index);
        }
        return $this;
    }

    /*
     * Defines an operation handler (without handling l-values and evaluations)
     */
    public function defOp($op, $function, $passOpName = false)
    {
        if (is_callable($function)) {
            $index = $this->indexFunction($function, !$passOpName);
            $this->doDefOp("op", $op, $this->createExFunction($function, !$passOpName, true), $index);
        }
        return $this;
    }

    /*
     * Defines the default operation handler (with handling l-values and evaluations)
     */
    public function defOpDefaultEx($function, $passOpName = true)
    {
        $this->defOpEx(null, $function, $passOpName);
        return $this;
    }

    /*
     * Defines the default operation handler (without handling l-values and evaluations)
     */
    public function defOpDefault($function, $passOpName = true)
    {
        $this->defOp(null, $function, $passOpName);
        return $this;
    }

    /*
     * Defines the variable handler
     */
    public function defVar($function)
    {
        $this->doDefOp("var", null, $function);
        return $this;
    }

    /*
     * Defines the constant handler
     */
    public function defConst($function)
    {
        $this->doDefOp("const", null, $function);
        return $this;
    }

    /*
     * Defines the handler of trying to access a normal value as an L-value.
     */
    public function defNotLvalue($function)
    {
        $this->doDefOp("nlv", null, $function);
        return $this;
    }

    private function doDefOp($prefix, $ops, $function, $index = null)
    {
        if (!is_array($ops)) {
            $ops = [$ops];
        }
        if (is_callable($function)) {
            foreach ($ops as $op) {
                if ($op === null) {
                    $key = $prefix;
                } else {
                    $key = $prefix . "_" . $op;
                }
                if ($index === null) {
                    $index = $this->indexFunction($function);
                }
                if (!isset($this->ops[$key])) {
                    $this->ops[$key] = [];
                }
                $this->ops[$key][$index] = $function;
            }
        }
    }


    private function createExFunction($function, $stripOp, $autoEval)
    {
        if ($stripOp) {
            $function = function (...$args) use ($function) {
                array_shift($args);
                return $function(...$args);
            };
        }
        if ($autoEval) {
            $function = function (...$args) use ($function) {
                $first = true;
                foreach ($args as &$v) {
                    if (!$first) {
                        $v = $v->value();
                    }
                    $first = false;
                }
                return $function(...$args);
            };
        }

        return $function;
    }

    private function notLValue(...$arguments)
    {
        return $this->callFunction("nlv", null, $arguments, null);
    }

    private function indexFunction($function, $addOne = false)
    {
        $rf = new ReflectionFunction($function);
        $params = $rf->getNumberOfParameters() + ($addOne ? 1 : 0);
        $rqParams = $rf->getNumberOfRequiredParameters() + ($addOne ? 1 : 0);
        $variadic = $rf->isVariadic() ? 'var' : 'fix';
        return "{$rqParams}_{$params}_{$variadic}";
    }

    private function scoreBetter($score, $bestScore)
    {
        if ($bestScore === null) {
            return true;
        }
        foreach ($score as $i => $v) {
            $vb = $bestScore[$i];
            if ($v < $vb) {
                return true;
            }
            if ($v > $vb) {
                return false;
            }
        }
        return false;
    }

    private function findBestIndex($n, $indices)
    {
        $best = null;
        $bestScore = null;
        foreach ($indices as $index) {
            list($rqp,$p,$v) = explode("_", $index);
            $missing = ($rqp > $n) ? $rqp - $n : 0;
            $nrq = ($n > $rqp && $n <= $p) ? ($n - $rqp) : (($n > $p) ? ($p - $rqp) : 0);
            $varp = ($n > $p) ? ($n - $p) : 0;
            if ($v == "fix" && $varp > 0) {
                $nv = 1;
            } else {
                $nv = 0;
            }
            $score = [$missing,$nrq,$nv,$varp];
            if ($this->scoreBetter($score, $bestScore)) {
                $best = $index;
                $bestScore = $score;
            }
        }
        return $best;
    }

    public function evalOp($variables, $op, $sub)
    {
        $args = [$op];
        foreach ($sub as $x) {
            $args[] = new LValueWrapper($x, $this, $variables, function (...$arguments) {
                $this->notLValue(...$arguments);
            });
        }
        return $this->callFunction("op", $op, $args, null);
    }

    public function evalVariable($variables, $name)
    {
        return $this->callFunction("var", null, [$name,$variables], function () use ($variables, $name) {
            if (is_array($variables) || is_a($variables, "ArrayAccess")) {
                return isset($variables[$name]) ? $variables[$name] : null;
            }
        }, true);
    }

    public function evalConstant($variables, $constant)
    {
        return $this->callFunction("const", null, [$constant,$variables], $constant);
    }

    private function callFunction($prefix, $op, $args, $default = null, $defaultCallable = false)
    {
        if ($op === null) {
            $keys = [$prefix];
        } else {
            $keys = [$prefix . "_" . $op,$prefix];
        }
        foreach ($keys as $key) {
            if (isset($this->ops[$key])) {
                $index = $this->findBestIndex(count($args), array_keys($this->ops[$key]));
                if ($index !== null && isset($this->ops[$key][$index])) {
                    $function = $this->ops[$key][$index];
                    return $function(...$args);
                }
            }
        }
        if ($defaultCallable) {
            return $default();
        } else {
            return $default;
        }
    }
}
