<?php

namespace Sterzik\Expression\Priv;

/*
 * This trait is used in combination with AbstractPreset.
 * Any class implementing this trait will be able to
 * provide some preset instances of that class.
 */

trait DefaultStorageTrait
{
    private static $defaultStorage = null;

    /*
     * Returns an instance of a class implementing this trait.
     * Possible values of $data:
     * - an instance of the class - the instance is returned then
     * - a string                 - then the corresponding preset is returned
     * - NULL                     - then the default instance is returned, if some
     *                              instance was marked as default. If no instance
     *                              was marked as default, the default preset
     *                              is returned
     */
    public static function get($data = null)
    {
        if (is_a($data, get_called_class())) {
            return $data;
        }
        if (!is_string($data)) {
            $data = null;
        }

        if ($data === null) {
            if (static::$defaultStorage === null) {
                static::$defaultStorage = static::getPreset("default");
            }
            return static::$defaultStorage;
        } else {
            return static::getPreset($data);
        }
    }

    /*
     * Get the default instance.
     */
    public static function getDefault()
    {
        return static::get();
    }

    /*
     * Mark the instance as default.
     */
    public function setDefault()
    {
        if (static::$defaultStorage === null) {
            static::$defaultStorage = static::getPreset("default");
        }
        $ret = static::$defaultStorage;
        static::$defaultStorage = $this;
        return $ret;
    }

    private static function getPreset($preset)
    {
        $class = static::$presetClass;
        if (substr($class, 0, 1) != '\\') {
            $class = __NAMESPACE__ . '\\' . $class;
        }
        $function = [$class,"get"];
        $result = $function($preset);
        return $result;
    }
}
