<?php

namespace Sterzik\Expression\Priv;

use Sterzik\Expression\Postprocessor;
use Sterzik\Expression\Expression;

/*
 * This class is a builder for Postprocessor instance presets.
 * Currently there are two presets:
 *  1. default - creates a default postprocessor used in combination with default ParserSettings
 *  2. empty - creates an empty postprocessor
 */
class PostprocessorPreset extends AbstractPreset
{
    public static function default()
    {
        $ps = new Postprocessor();

        $ps->setPostprocessOp("()", function ($expr) {
            return $expr[0];
        });

        $mfn = function ($expr, $op) {
            $n = count($expr);
            if ($n < 1) {
                return $expr;
            }
            if ($op == "fn()" && $n == 1) {
                return $expr;
            }
            $sub = $expr[$n - 1];

            if ($sub->type() == "op" && $sub->op() == ",") {
                $x = [];
                for ($i = 0; $i < $n - 1; $i++) {
                    $x[] = $expr[$i];
                }
                $x = array_merge($x, $sub->arguments());
                return Expression::create("op", $op, $x);
            } else {
                return $expr;
            }
        };
        $ps->setPostprocessOp("[]", $mfn);
        $ps->setPostprocessOp("fn()", $mfn);
        return $ps;
    }

    public static function createEmpty()
    {
        return new Postprocessor();
    }
}
