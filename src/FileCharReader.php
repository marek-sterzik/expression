<?php

namespace Sterzik\Expression;

/*
 * Provides the CharReader interface for an open file.
 */
class FileCharReader extends CharReader
{
    private $fd;

    /*
     * Construct the CharReader according to the file descriptor
     */
    public function __construct($fd)
    {
        if (is_resource($fd)) {
            $this->fd = $fd;
        } else {
            $this->fd = null;
        }
    }

    /*
     * Read a single character from the file
     */
    public function read()
    {
        if ($this->fd === null) {
            return null;
        }
        $c = fgetc($this->fd);
        if ($c === false) {
            return null;
        }
        return $c;
    }
}
