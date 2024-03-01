<?php

namespace Sterzik\Expression\Priv;

use Sterzik\Expression\Evaluator;
use Sterzik\Expression\LValueBuilder;

/*
 * This class contains the definition of all preset instances of the Evaluator
 */
class EvaluatorPreset extends AbstractPreset
{
    /*
     * This is the default evaluator (able to evaluate standard php-like expressions)
     */
    public static function default()
    {
        $ev = new Evaluator();
        $ev->defOp("+", function ($a, $b) {
            return $a + $b;
        });
        $ev->defOp("+", function ($a) {
            return + $a;
        });
        $ev->defOp("-", function ($a, $b) {
            return $a - $b;
        });
        $ev->defOp("-", function ($a) {
            return -$a;
        });
        $ev->defOp("!", function ($a) {
            return !$a;
        });
        $ev->defOp("*", function ($a, $b) {
            return $a * $b;
        });
        $ev->defOp("/", function ($a, $b) {
            return $a / $b;
        });
        $ev->defOp("%", function ($a, $b) {
            return $a % $b;
        });
        $ev->defOp(">", function ($a, $b) {
            return $a > $b;
        });
        $ev->defOp(">=", function ($a, $b) {
            return $a >= $b;
        });
        $ev->defOp("<", function ($a, $b) {
            return $a < $b;
        });
        $ev->defOp("<=", function ($a, $b) {
            return $a <= $b;
        });
        $ev->defOp("==", function ($a, $b) {
            return $a == $b;
        });
        $ev->defOp("!=", function ($a, $b) {
            return $a != $b;
        });
        $ev->defOpEx("&&", function ($a, $b) {
            return $a->value() && $b->value();
        });
        $ev->defOpEx("||", function ($a, $b) {
            return $a->value() || $b->value();
        });
        $ev->defOpEx("?:", function ($a, $b, $c) {
            return $a->value() ? $b->value() : $c->value();
        });
        $ev->defOpEx("=", function ($a, $b) {
            $v = $b->value();
            $a->assign($v);
            return $v;
        });
        $ev->defVar(function ($var, $vars) {
            if (!is_object($vars)) {
                if (is_array($vars)) {
                    return isset($vars[$var]) ? $vars[$var] : null;
                } else {
                    return $vars;
                }
            } else {
                if (is_a($vars, "ArrayAccess")) {
                    $l = new LValueBuilder();
                    $l->value(function () use ($var, $vars) {
                        return isset($vars[$var]) ? $vars[$var] : null;
                    });
                    $l->assign(function ($val) use ($var, $vars) {
                        $vars[$var] = $val;
                        return $val;
                    });
                    return $l->getLValue();
                } else {
                    return $vars;
                }
            }
        });
        return $ev;
    }

    /*
     * This is the empty evaluator (used for custom evaluator builds)
     */
    public static function createEmpty()
    {
        return new Evaluator();
    }
}
