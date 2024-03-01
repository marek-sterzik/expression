<?php

namespace Sterzik\Expression;

use Exception;

/*
 * The Postprocessor is used for postprocessing expressions.
 */
class Postprocessor
{
    use Priv\DefaultStorageTrait;

    private $pf;
    private static $presetClass = "PostprocessorPreset";

    public function __construct()
    {
        $this->pf = [];
    }

    /*
     * Sets an operation postprocessing handler
     */
    public function setPostprocessOp($op, $fun)
    {
        $i = $this->getIndex("op", $op);
        $this->pf[$i] = $fun;
        return $this;
    }

    /*
     * Sets the default operation postprocessing handler
     * (used if no operation postprocessing handler was defined for the operation)
     */
    public function setDefaultPostprocessOp($fun)
    {
        return $this->setPostprocessOp(null, $fun);
    }

    /*
     * Sets the default constant postprocessing handler
     */
    public function setPostprocessConst($fun)
    {
        $i = $this->getIndex("const", null);
        $this->pf[$i] = $fun;
        return $this;
    }

    /*
     * Sets the default variable postprocessing handler
     */
    public function setPostprocessVar($fun)
    {
        $i = $this->getIndex("var", null);
        $this->pf[$i] = $fun;
        return $this;
    }

    public function postprocessOp($op, $expr)
    {
        $invoked = false;
        $rv = $this->invoke("op", $op, $invoked, $expr, $op);
        if (!$invoked) {
            $rv = $this->invoke("op", null, $invoked, $expr, $op);
        }
        if (!$invoked || $rv === null) {
            return $expr;
        }
        return $rv;
    }

    public function postprocessConst($expr)
    {
        $invoked = false;
        $rv = $this->invoke("const", null, $invoked, $expr);
        if (!$invoked || $rv === null) {
            return $expr;
        }
        return $rv;
    }

    public function postprocessVar($expr)
    {
        $invoked = false;
        $rv = $this->invoke("var", null, $invoked, $expr);
        if (!$invoked || $rv === null) {
            return $expr;
        }
        return $rv;
    }

    private function getIndex($c1, $c2)
    {
        return $c1 . "_" . (($c2 === null) ? ('n') : ('_' . $c2));
    }

    private function invoke($c1, $c2, &$invoked, ...$arguments)
    {
        $i = $this->getIndex($c1, $c2);
        if (!isset($this->pf[$i]) || !is_callable($this->pf[$i])) {
            $invoked = false;
            return null;
        } else {
            $invoked = true;
            $fun = $this->pf[$i];
            return $fun(...$arguments);
        }
    }
}
