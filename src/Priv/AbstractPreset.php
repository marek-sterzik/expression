<?php

namespace Sterzik\Expression\Priv;

/*
 * The class AbstractPreset just defines a common code for classes containing
 * some preset instances. In the current implementation it is used by two
 * classes: ParserPreset and EvaluatorPreset.
 */

abstract class AbstractPreset
{
    /*
     * Get a given preset. The preset "default" has a special meaning.
     * It must be implemented in any preset. Other presets are optional.
     * Any preset has the same name as the apropriate method.
     */

    public static function get($preset = null)
    {
        if (!is_string($preset)) {
            $preset = "default";
        }
        $function = [get_called_class(),$preset];
        if (!is_callable($function) && $preset != 'default') {
            $function[1] = "default";
        }
        if (!is_callable($function)) {
            return null;
        }
        return $function();
    }


    /*
     * This function returns the default preset. It must be implemented.
     */
    abstract public static function default();
}
