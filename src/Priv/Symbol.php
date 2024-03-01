<?php

namespace Sterzik\Expression\Priv;

/*
 * This class is used only internally by the parser and should not be used.
 */
class Symbol
{
    private $type;
    private $data;

    public static function get($type, $data)
    {
        return new self($type, $data);
    }

    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function type()
    {
        return $this->type;
    }

    public function data($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}
