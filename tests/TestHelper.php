<?php

namespace Tests;

use Sterzik\Expression\Parser;
use Sterzik\Expression\Evaluator;

class TestHelper
{
    public static function getStructureEvaluator()
    {
        $ev = new Evaluator();
        $ev->defVar(function ($var) {
            return $var;
        });
        $ev->defConst(function ($const) {
            return json_encode($const);
        });
        $ev->defOpDefault(function (...$args) {
            $op = array_shift($args);
            return $op."(".implode(",", $args).")";
        });
        return $ev;
    }

    public static function parse($expr, $parserSettings = null)
    {
        $parser = new Parser($parserSettings);
        return $parser->parse($expr);
    }

    public static function structure($expr)
    {
        if ($expr === null) {
            return null;
        }
        return $expr->evaluate(null, static::getStructureEvaluator());
    }
}
