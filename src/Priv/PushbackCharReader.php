<?php

namespace Sterzik\Expression\Priv;

use Sterzik\Expression\CharReader;

/*
 * Extends any CharReader interface to provide the unread functionality.
 * The PushbackCharReader is used mostly for internal reasons. It is not necessary
 * to operate with it in the external code.
 */
class PushbackCharReader extends CharReader
{
    private $charReader;
    private $buffer;

    /*
     * The pushback char reader is always constructed internally.
     * Don't call the constructor directly.
     */
    public function __construct($charReader)
    {
        $this->charReader = $charReader;
        $this->buffer = "";
    }

    /*
     * Provides the function of reading one char (same like CharReader)
     */
    public function read()
    {
        if ($this->buffer == '') {
            return $this->charReader->read();
        } else {
            $char = $this->buffer[0];
            $this->buffer = substr($this->buffer, 1);
            return $char;
        }
    }

    /*
     * Provides the functionality of pushing some string back to the buffer (for later reading again)
     * It is guaranted, that the string to unread exactly matches the characters, which were already
     * read. It is only unclear the length of the string, which may be more than one character.
     */
    public function unread($str)
    {
        $this->buffer = $str . $this->buffer;
    }

    /*
     * This function is used for creating pushback char readers. We don't need to do anything if we already
     * have one.
     */
    public function getPushbackCharReader()
    {
        return $this;
    }
}
