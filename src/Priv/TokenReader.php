<?php

namespace Sterzik\Expression\Priv;

/*
 * Provides an interface for reading the input as tokens.
 * This class is used only internally by the parser.
 *
 */
class TokenReader
{
    private $charReader;
    private $operators;

    public function __construct($charReader, $trSettings)
    {
        $this->charReader = $charReader;
        $this->operators = $trSettings['operators'];
    }

    public function read()
    {
        $char = $this->charReader->read();
        while ($char !== null && ctype_space($char)) {
            $char = $this->charReader->read();
        }
        if ($char === null) {
            return null;
        }
        if (preg_match("/[a-zA-Z0-9_]/", $char)) {
            $id = $char;
            $char = $this->charReader->read();
            while ($char !== null && preg_match("/[a-zA-Z0-9_]/", $char)) {
                $id .= $char;
                $char = $this->charReader->read();
            }
            $this->charReader->unread($char);
            if (preg_match("/^[0-9]/", $id)) {
                if (preg_match("/^[0-9]+$/", $id)) {
                    if ($id[0] == '0') {
                        return Token::get("constant", (int)octdec($id));
                    } else {
                        return Token::get("constant", (int)$id);
                    }
                } elseif (preg_match("/^0x[0-9]+$/", $id)) {
                    return Token::get("constant", (int)hexdec(substr($id, 2)));
                } else {
                    return Token::get("unknown", $id);
                }
            } else {
                return Token::get("identifier", $id);
            }
        } elseif ($char == "'" || $char == '"') {
            $x = $this->charReader->read();
            $str = "";
            while ($x !== null && $x != $char) {
                if ($x == "\\") {
                    $x = $this->charReader->read();
                    if ($x === null) {
                        return Token::get("unknown", null);
                    }
                    switch ($x) {
                        case 'n':
                            $x = "\n";
                            break;
                        case 'r':
                            $x = "\r";
                            break;
                        case 'b':
                            $x = "\b";
                            break;
                        case 't':
                            $x = "\t";
                            break;
                        case "\\":
                            $x = "\\";
                            break;
                        default:
                    }
                }
                $str .= $x;
                $x = $this->charReader->read();
            }
            if ($x === null) {
                return Token::get("unknown", null);
            }
            return Token::get("constant", $str);
        } elseif (static::isOpChar($char)) {
            $op = "";
            $unread = "";
            $fo = null;
            do {
                $op .= $char;
                $unread .= $char;
                if (in_array($op, $this->operators)) {
                    $fo = $op;
                    $unread = "";
                }
                $char = $this->charReader->read();
            } while ($this->isOpChar($char));

            if ($fo !== null) {
                if ($char === null) {
                    $char = "";
                }
                $this->charReader->unread($unread . $char);

                return Token::get("operator", $fo);
            }
            $this->charReader->unread($char);
            return Token::get("unknown", $op);
        } else {
            return Token::get("unknown", $char);
        }
    }

    public static function isOpChar($char)
    {
        return in_array(
            $char,
            ['(',')','=','&','|','!','~','>','<','+','-','*','/','%','^','@',',','.','?',':','[',']','{','}']
        );
    }
}
