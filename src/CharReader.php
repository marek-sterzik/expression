<?php

namespace Sterzik\Expression;

use Exception;

/*
 * The class CharReader defines a simple interface necessary
 * for the expression parser. Any abstract char reader needs implement just
 * the function read().
 */
abstract class CharReader
{
    /*
     * Read a single character.
     * Return the next character or NULL if there is none there.
     */
    abstract public function read();

    /*
     * Gets the char reader associated with the given object. For now, these objects are supported:
     *  - strings
     *  - file descriptors
     *  - objects of this class
     */
    public static function get($x)
    {
        if (is_a($x, __CLASS__)) {
            return $x;
        } elseif (is_string($x)) {
            return new StringCharReader($x);
        } elseif (is_resource($x)) {
            return new FileCharReader($x);
        } else {
            throw new Exception("Unknown type");
        }
    }

    /*
     * Get the pushback char reader, which is able to "unread" some characters.
     */
    public function getPushbackCharReader()
    {
        return new Priv\PushbackCharReader($this);
    }

    /*
     * Gets the token reader associated with that char reader
     */
    public function getTokenReader($trSettings)
    {
        return new Priv\TokenReader($this->getPushbackCharReader(), $trSettings);
    }
}
