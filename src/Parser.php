<?php

namespace Sterzik\Expression;

use Exception;

/*
 * This class represents the expression parser itself. It is responsible for
 * parsing any expressions.
 */
class Parser
{
    private $parserSettings;
    private $throwExceptions;
    private $lastError;

    /*
     * Creation a new parser.
     * Arguments:
     *  $parserSettings  - the instance of the ParserSettings which should be used to parse the expression
     *                     If no instance is given, the default instance (ParserSettings::get(NULL)) is used.
     *  $throwExceptions - If true, parsing errors will cause throwing exceptions
     *                     If false, parsing errors will return null with the possibility of calling getLastError()
     */
    public function __construct($parserSettings = null, $throwExceptions = false)
    {
        $this->parserSettings = ParserSettings::get($parserSettings);
        $this->throwExceptions = $throwExceptions;
        $this->lastError = null;
    }

    /*
     * Parse Any expression
     * Arguments:
     *  $expr - the expression to be parsed. The expression is passed to CharReader::get(), so the expression may
     *          have the same format as this method accepts.
     *
     * Return value:
     *  - An instance of Expression if successful
     *  - NULL if parsing failed ($throwExceptions must be false to return NULL here)
     */
    public function parse($expr)
    {
        $this->lastError = null;
        try {
            return $this->doParse($expr);
        } catch (ParserException $e) {
            if ($this->throwExceptions) {
                throw $e;
            } else {
                $this->lastError = $e;
                return null;
            }
        }
    }

    /*
     * Returns the error message of the last parsing.
     */
    public function getLastError()
    {
        if ($this->lastError === null) {
            return null;
        }
        return $this->lastError->getMessage();
    }

    /*
     * Dynamically changes the $throwExceptions to a new value
     */
    public function throwExceptions($throwExceptions = true)
    {
        $tmp = $this->throwExceptions;
        $this->throwExceptions = $throwExceptions ? true : false;
        return $tmp;
    }

    private function doParse($expr)
    {
        $trSettings = [
            "operators" => $this->parserSettings->getAllOps(),
        ];
        $r = CharReader::get($expr)->getTokenReader($trSettings);
        $eb = new Priv\ExpressionBuilder();
        while (($token = $r->read()) !== null) {
            $context = $eb->getContext();
            $symbol = $this->parserSettings->getSymbol($token, $context);
            if ($symbol === null) {
                $t = $token->data();
                throw new ParserException("Unexpected token: $t");
            }
            $eb->pushSymbol($symbol);
        }
        $result = $eb->getExpression();
        $result = $result->toExpression();
        $result = $result->postprocess($this->parserSettings->getPostprocessor());
        if ($result === false) {
            throw new ParserException("Postprocess failed");
        }

        return $result;
    }
}
