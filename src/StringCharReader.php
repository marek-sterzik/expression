<?php

namespace Sterzik\Expression;

/*
 * Provides the CharReader interface for a string.
 */
class StringCharReader extends CharReader
{
    private $len;
    private $string;
    private $index;

    /*
     * Construct the CharReader according to the string
     */
    public function __construct($str)
    {
        $this->len = strlen($str);
        $this->string = $str;
        $this->index = 0;
    }

    /*
     * Read a single character from the string
     */
    public function read()
    {
        if ($this->index >= $this->len) {
            return null;
        }
        return $this->string[$this->index++];
    }
}
