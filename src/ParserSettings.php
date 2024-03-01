<?php

namespace Sterzik\Expression;

use Exception;

/*
 * This class is used to represent parser settings. It particulary contains:
 *  - all operators and their properties, which are parsable
 *  - all non-standard constants
 *
 * Even in the future, it will contain any data necessary for the parsing process
 */
final class ParserSettings
{
    use Priv\DefaultStorageTrait;

    private static $presetClass = "ParserPreset";

    private $cs; //case sensitive mode;
    private $cp; //current priority
    private $arity;
    private $lt; //Lookup Table
    private $uuid;
    private $postprocessor;

    /*
     * The constructor should not be called directly, use
     * ParserSettings::get("empty") instead.
     */
    public function __construct()
    {
        $this->cs = true;
        $this->cp = null;
        $this->arity = "l";
        $this->lt = [];
        $this->uuid = 0;
        $this->postprocessor = new Postprocessor();
    }

    /*
     * Set the priority of operators added later.
     */
    public function opPriority($priority)
    {
        $this->checkPriority($priority);
        $this->cp = (int)$priority;
        return $this;
    }

    /*
     * Set the arity of operators added later. The arity may be:
     * 1. "L", "LR", "->", ">" (meaning arity from left to right)
     * 2. "R", "RL", "<-", "<" (meaning arity from right to left)
     */
    public function opArity($arity)
    {
        $this->checkArity($arity);
        $this->arity = $arity;
        return $this;
    }

    /*
     * Sets if the operators added later will be case sensitive or case insensitive.
     * This of course makes sense only for alphanumeric operators.
     */
    public function caseSensitive($cs = true)
    {
        $this->cs = $cs ? true : false;
        return $this;
    }

    /*
     * Add a binary operator
     */
    public function addBinaryOp($alias, $op = null)
    {
        if ($op === null) {
            $op = $alias;
        }
        $this->checkPriority($this->cp);
        $this->checkIdOp($op);
        $record = [
            "scope" => "operator",
            "id" => $op,
            "uuid" => $this->uuid(),
            "cs" => $this->cs,
            "type" => "multinary",
            "alias" => $alias,
            "priority" => $this->cp,
            "i" => 0,
            "n" => 1,
            "arity" => $this->arity,
        ];
        $this->addRecord($record);
        return $this;
    }

    /*
     * Add an unary prefix operator
     */
    public function addPrefixOp($alias, $op = null)
    {
        if ($op === null) {
            $op = $alias;
        }
        $this->checkPriority($this->cp);
        $this->checkIdOp($op);
        $record = [
            "scope" => "value",
            "id" => $op,
            "uuid" => $this->uuid(),
            "cs" => $this->cs,
            "type" => "prefix",
            "alias" => $alias,
            "priority" => $this->cp,
            "arity" => $this->arity,
        ];
        $this->addRecord($record);
        return $this;
    }

    /*
     * Add an unary postfix operator
     */
    public function addPostfixOp($alias, $op = null)
    {
        if ($op === null) {
            $op = $alias;
        }
        $this->checkPriority($this->cp);
        $this->checkIdOp($op);
        $record = [
            "scope" => "operator",
            "id" => $op,
            "uuid" => $this->uuid(),
            "cs" => $this->cs,
            "type" => "postfix",
            "alias" => $alias,
            "priority" => $this->cp,
            "arity" => $this->arity,
        ];
        $this->addRecord($record);
        return $this;
    }

    /*
     * Add a multinary operator
     */
    public function addMultinaryOp($alias, ...$ops)
    {
        $this->checkPriority($this->cp);
        $this->checkIdOp($alias);
        if (count($ops) < 1) {
            throw new Exception("Nnary Op needs to have defined at least one operator.");
        }

        foreach ($ops as $op) {
            $this->checkIdOp($op);
        }
        $n = count($ops);
        $i = 0;
        $uuid = $this->uuid();
        foreach ($ops as $op) {
            $record = [
                "scope" => "operator",
                "id" => $op,
                "uuid" => $uuid,
                "cs" => $this->cs,
                "type" => "multinary",
                "alias" => $alias,
                "i" => $i++,
                "n" => $n,
                "priority" => $this->cp,
                "arity" => $this->arity,
            ];
            $this->addRecord($record);
        }
        return $this;
    }

    /*
     * Add a multinary variadic operator
     */
    public function addVariadicOp($alias, $op = null)
    {
        if ($op === null) {
            $op = $alias;
        }
        $this->checkPriority($this->cp);
        $this->checkIdOp($op);
        $record = [
            "scope" => "operator",
            "id" => $op,
            "uuid" => $this->uuid(),
            "cs" => $this->cs,
            "type" => "variadic",
            "alias" => $alias,
            "priority" => $this->cp,
            "arity" => $this->arity,
        ];
        $this->addRecord($record);
        return $this;
    }

    /*
     * Add parenthesis
     */
    public function addParenthesis($alias, $beginOp, $endOp, $allowEmpty)
    {
        $this->checkIdOp($beginOp);
        $this->checkIdOp($endOp);
        $uuid = $this->uuid();
        $record = [
            "scope" => "value",
            "id" => $beginOp,
            "uuid" => $uuid,
            "cs" => $this->cs,
            "type" => "(",
            "alias" => $alias,
            "empty" => $allowEmpty,
        ];
        $this->addRecord($record);
        $record = [
            "scope" => ["value","operator"],
            "id" => $endOp,
            "cs" => $this->cs,
            "type" => ")",
            "alias" => $alias,
            "uuids" => [$uuid],
        ];
        $this->addCloseParenthesisRecord($record);
        return $this;
    }

    /*
     * Add prefix index. Like: <A+C>B
     */
    public function addPrefixIndex($alias, $beginOp, $endOp, $allowEmpty)
    {
        $this->checkPriority($this->cp);
        $this->checkIdOp($beginOp);
        $this->checkIdOp($endOp);
        $uuid = $this->uuid();
        $record = [
            "scope" => "value",
            "id" => $beginOp,
            "uuid" => $uuid,
            "cs" => $this->cs,
            "type" => "prefix(",
            "alias" => $alias,
            "priority" => $this->cp,
            "arity" => $this->arity,
            "empty" => $allowEmpty,
        ];
        $this->addRecord($record);
        $record = [
            "scope" => ["value","operator"],
            "id" => $endOp,
            "cs" => $this->cs,
            "type" => ")",
            "uuids" => [$uuid],
        ];
        $this->addCloseParenthesisRecord($record);
        return $this;
    }

    /*
     * Add postfix index. Like: A[B+C]
     */
    public function addPostfixIndex($alias, $beginOp, $endOp, $allowEmpty)
    {
        $this->checkPriority($this->cp);
        $this->checkIdOp($beginOp);
        $this->checkIdOp($endOp);
        $uuid = $this->uuid();
        $record = [
            "scope" => "operator",
            "id" => $beginOp,
            "uuid" => $uuid,
            "cs" => $this->cs,
            "type" => "postfix(",
            "alias" => $alias,
            "priority" => $this->cp,
            "arity" => $this->arity,
            "empty" => $allowEmpty,
        ];
        $this->addRecord($record);
        $record = [
            "scope" => ["value","operator"],
            "id" => $endOp,
            "cs" => $this->cs,
            "type" => ")",
            "uuids" => [$uuid],
        ];
        $this->addCloseParenthesisRecord($record);
        return $this;
    }

    /*
     * Add a custom constant.
     */
    public function addConstant($identifier, $value)
    {
        $this->checkId($identifier);
        $record = [
            "scope" => "value",
            "id" => $identifier,
            "uuid" => $this->uuid(),
            "cs" => $this->cs,
            "type" => "constant",
            "value" => $value];
        $this->addRecord($record);
        return $this;
    }

    /*
     * Setup postprocessing for operations.
     */
    public function setPostprocessOp($op, $fun)
    {
        $this->postprocessor->setPostprocessOp($op, $fun);
        return $this;
    }

    /*
     * Setup default postprocessing for operations
     * (will be used if no postprocessing is defined for the particular operation).
     */
    public function setDefaultPostprocessOp($fun)
    {
        $this->postprocessor->setDefaultPostprocessOp($fun);
    }

    /*
     * Setup postprocessing for variables.
     */
    public function setPostprocessVar($fun)
    {
        $this->postprocessor->setPostProcessVar($fun);
        return $this;
    }

    /*
     * Setup postprocessing for constants.
     */
    public function setPostprocessConst($fun)
    {
        $this->postprocessor->setPostProcessConst($fun);
        return $this;
    }

    /*
     * Sets the whole postprocessor
     */
    public function setPostprocessor($postprocessor)
    {
        $this->postprocessor = Postprocessor::get($postprocessor);
    }


    private function uuid()
    {
        return "uuid:" . ($this->uuid++);
    }

    private function addCloseParenthesisRecord($record)
    {
        $x = $this->calcIndices($record);
        $indices  = $x['indices'];
        $blocking = $x['blocking'];

        foreach (array_merge($indices, $blocking) as $i) {
            if (isset($this->lt[$i])) {
                $r = $this->lt[$i];
                if ($r !== "blocking-par" && (!is_array($r) || $r['type'] != ")")) {
                    $this->throwRecordAlreadyDefined($record['id']);
                }
            }
        }
        foreach ($indices as $i) {
            if (!isset($this->lt[$i]) || !is_array($this->lt[$i])) {
                $this->lt[$i] = $record;
            } else {
                $this->lt[$i]['uuids'] = array_merge($this->lt[$i]['uuids'], $record['uuids']);
            }
        }
        foreach ($blocking as $i) {
            if (!isset($this->lt[$i])) {
                $this->lt[$i] = "blocking-par";
            }
        }
    }

    private function addRecord($record)
    {

        $x = $this->calcIndices($record);
        $indices  = $x['indices'];
        $blocking = $x['blocking'];

        foreach (array_merge($indices, $blocking) as $i) {
            if (isset($this->lt[$i])) {
                $this->throwRecordAlreadyDefined($record['id']);
            }
        }
        foreach ($indices as $i) {
            $this->lt[$i] = $record;
        }
        foreach ($blocking as $i) {
            $this->lt[$i] = "blocking";
        }
    }

    private function calcIndices($record)
    {
        if ($record['cs']) {
            $id = $record['id'];
            $cs = "s";
            $csBlocking = "i";
        } else {
            $id = strtolower($record['id']);
            $cs = "i";
            $csBlocking = null;
        }
        $scopes = $record['scope'];
        if (!is_array($scopes)) {
            $scopes = [$scopes];
        }

        $indices = [];
        $blocking = [];

        foreach ($scopes as $scope) {
            $index = "{$cs}_{$scope}_{$id}";
            $indices[] = $index;
            if ($csBlocking !== null) {
                $index = "{$csBlocking}_{$scope}_{$id}";
                $blocking[] = $index;
            }
        }
        return [
            "indices" => $indices,
            "blocking" => $blocking,
        ];
    }

    private function throwRecordAlreadyDefined($id)
    {
        throw new Exception("Record already defined: $id");
    }

    private function lookupRecord($scope, $id)
    {
        $indexI = "i_{$scope}_" . strtolower($id);
        $indexS = "s_{$scope}_{$id}";
        $record1 = null;
        $record2 = null;
        if (isset($this->lt[$indexI]) && is_array($this->lt[$indexI])) {
            $record1 = $this->lt[$indexI];
        }
        if (isset($this->lt[$indexS]) && is_array($this->lt[$indexS])) {
            $record2 = $this->lt[$indexS];
        }
        if ($record1 !== null && $record2 !== null) {
            if ($record1['type'] == ")" && $record2['type'] == ")") {
                $record = $record1;
                $record['uuids'] = array_merge($record['uuids'], $record2['uuids']);
            } else {
                $record = $record1;
            }
        } elseif ($record1 !== null) {
            $record = $record1;
        } else {
            $record = $record2;
        }
        unset($record['scope']);
        unset($record['id']);
        unset($record['cs']);
        return $record;
    }

    private function checkId($x)
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $x)) {
            throw new Exception("Invalid identifier: $x");
        }
    }

    private function checkOp($x)
    {
        $l = strlen($x);
        if ($l <= 0) {
            return false;
        }
        for ($i = 0; $i < $l; $i++) {
            if (!Priv\TokenReader::isOpChar($x[$i])) {
                throw new Exception("Invalid operator: $x");
            }
        }
    }

    private function checkIdOp($x)
    {
        try {
            $this->checkId($x);
            return;
        } catch (Exception $e) {
        }
        $this->checkOp($x);
    }

    private function checkPriority($x)
    {
        if (!is_int($x) || $x < 0) {
            throw new Exception("Invalid Priority");
        }
    }

    private function checkArity(&$x)
    {
        if (!is_string($x)) {
            throw new Exception("Invalid Arity");
        }
        switch (strtolower($x)) {
            case 'l':
            case 'lr':
            case '->':
            case '>':
                $x = "l";
                break;
            case 'r':
            case 'rl':
            case '<-':
            case '<':
                $x = "r";
                break;
            default:
                throw new Exception("Invalid Arity");
        }
    }

    public function getSymbol($token, $context)
    {
        $type = $token->type();
        switch ($type) {
            case 'identifier':
            case 'operator':
                $op = $token->data();
                $r = null;

                if ($r !== null) {
                    $op = $r['var'];
                }
                $r = $this->lookupRecord($context['mode'], $op);

                if ($r === null) {
                    if ($type == "identifier") {
                        return Priv\Symbol::get("variable", ["name" => $op]);
                    } else {
                        return null;
                    }
                }
                $type = $r['type'];
                unset($r['type']);
                return Priv\Symbol::get($type, $r);
            case 'constant':
                return Priv\Symbol::get("constant", ["value" => $token->data()]);
            default:
                return null;
        }
    }

    public function getAllOps()
    {
        $ops = [];
        foreach ($this->lt as $key => $r) {
            if (!is_array($r)) {
                continue;
            }
            $id = $r['id'];
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $id)) {
                $ops[$id] = true;
            }
        }
        return array_keys($ops);
    }

    public function getPostprocessor()
    {
        return $this->postprocessor;
    }
}
