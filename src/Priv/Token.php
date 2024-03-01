<?php

namespace Sterzik\Expression\Priv;

/*
 * This class is used only internally by the parser and should not be used.
 */
class Token
{
    private $type;
    private $data;

    public static function get($type, $data)
    {
        return new self($type, $data);
    }

    private function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public static function constant($value)
    {
        return new self("constant", ["value" => $value]);
    }

    public function type()
    {
        return $this->type;
    }

    public function data()
    {
        return $this->data;
    }
}
