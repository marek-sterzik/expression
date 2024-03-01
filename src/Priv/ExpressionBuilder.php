<?php

namespace Sterzik\Expression\Priv;

use Sterzik\Expression\ParserException;

/*
 * This class is internally used by the Parser. It is not intended to be used externally.
 * The class represents the partially created expression.
 */
class ExpressionBuilder
{
    private $expr;
    private $valueMode;
    private $pointer;

    public function __construct()
    {
        $this->expr = null;
        $this->valueMode = true;
        $this->pointer = null;
    }

    public function pushSymbol($symbol)
    {
        switch ($symbol->type()) {
            case 'multinary':
                if ($symbol->data("i") == 0) {
                    return $this->pushOperatorFirst(
                        $symbol->data("uuid"),
                        $symbol->data("alias"),
                        $symbol->data("priority"),
                        $symbol->data("arity"),
                        $symbol->data("n")
                    );
                } else {
                    return $this->pushOperatorNext(
                        $symbol->data("uuid"),
                        $symbol->data("i")
                    );
                }
            case 'variadic':
                return $this->pushVariadicOperator(
                    $symbol->data("uuid"),
                    $symbol->data("alias"),
                    $symbol->data("priority"),
                    $symbol->data("arity")
                );
            case 'prefix':
                return $this->pushPrefix(
                    $symbol->data("alias"),
                    $symbol->data("priority"),
                    $symbol->data("arity")
                );
            case 'postfix':
                return $this->pushPostfix(
                    $symbol->data("alias"),
                    $symbol->data("priority")
                );
            case '(':
                return $this->pushParenthesis(
                    $symbol->data("uuid"),
                    $symbol->data("alias"),
                    $symbol->data("empty")
                );
            case 'prefix(':
                return $this->pushPrefixIndex(
                    $symbol->data("uuid"),
                    $symbol->data("alias"),
                    $symbol->data("priority"),
                    $symbol->data("arity"),
                    $symbol->data("empty")
                );
            case 'postfix(':
                return $this->pushPostfixIndex(
                    $symbol->data("uuid"),
                    $symbol->data("alias"),
                    $symbol->data("priority"),
                    $symbol->data("empty")
                );
            case ')':
                return $this->pushCloseParenthesis(
                    $symbol->data("uuids")
                );
            case 'constant':
                return $this->pushConstant(
                    $symbol->data("value")
                );
            case 'variable':
                return $this->pushVariable(
                    $symbol->data("name")
                );
            default:
                throw new ParserException("unknown symbol");
        }
    }

    public function getContext()
    {
        return [
            "mode" => $this->valueMode ? 'value' : 'operator',
        ];
    }

    public function pushPrefix($alias, $priority, $arity)
    {
        $this->valueModeExpected();
        $expr = ProtoExpression::createPrefix($alias, $priority, $arity);
        $this->connect($expr);
    }

    public function pushPostfix($alias, $priority)
    {
        $this->operatorModeExpected();
        $this->pointerToPriority($priority);
        $expr = ProtoExpression::createPostfix($alias, $priority);
        $operand = $this->replace($expr);
        $expr->addSubexpression($operand);
        $this->pointer = $expr;
    }

    public function pushOperatorFirst($id, $alias, $priority, $arity, $n)
    {
        $this->operatorModeExpected();
        $this->pointerToPriority($priority);
        $expr = ProtoExpression::createOperator($id, $alias, $priority, $arity, $n);
        $operand = $this->replace($expr);
        $expr->addSubexpression($operand);
        $this->pointer = $expr;
        $this->valueMode = true;
    }

    public function pushOperatorNext($id, $i)
    {
        $this->operatorModeExpected();
        $this->pointerToBlocking();
        if (
            $this->pointer === null ||
            $this->pointer->id() !== $id ||
            $this->pointer->getNumSubexpressions() != $i + 1
        ) {
            throw new ParserException("cannot add part of a multinary operator: operator is not accessible");
        }
        $this->valueMode = true;
    }

    public function pushVariadicOperator($id, $alias, $priority, $arity)
    {
        $this->operatorModeExpected();
        do {
            if ($this->pointer->id() === $id) {
                $this->valueMode = true;
                return;
            }
        } while ($this->pointerMoveUpToPrio($priority));

        $expr = ProtoExpression::createVariadicOperator($id, $alias, $priority, $arity);
        $operand = $this->replace($expr);
        $expr->addSubexpression($operand);
        $this->pointer = $expr;
        $this->valueMode = true;
    }

    public function pushParenthesis($id, $alias, $allowEmpty)
    {
        $this->valueModeExpected();
        $expr = ProtoExpression::createParenthesis($id, $alias, $allowEmpty);
        $this->connect($expr);
    }


    public function pushPrefixIndex($id, $alias, $priority, $arity, $allowEmpty)
    {
        $this->valueModeExpected();
        $expr = ProtoExpression::createPrefixIndex($id, $alias, $priority, $arity, $allowEmpty);
        $this->connect($expr);
    }

    public function pushPostfixIndex($id, $alias, $priority, $allowEmpty)
    {
        $this->operatorModeExpected();
        $this->pointerToPriority($priority);
        $expr = ProtoExpression::createPostfixIndex($id, $alias, $priority, $allowEmpty);
        $operand = $this->replace($expr);
        $expr->addSubexpression($operand);
        $this->pointer = $expr;
        $this->valueMode = true;
    }

    public function pushCloseParenthesis($ids)
    {
        #first test for an empty parenthesis
        $emptyPar = false;
        if (
            $this->valueMode &&
            $this->pointer !== null &&
            $this->pointer->isParenthesis() &&
            $this->pointer->allowEmpty() &&
            in_array($this->pointer->id(), $ids)
        ) {
            $n = $this->pointer->getNumSubexpressions();
            if ($this->pointer->getParenthesisType() == "postfix") {
                $emptyPar = $n <= 1;
            } else {
                $emptyPar = $n <= 0;
            }
        }
        if (!$emptyPar) {
            $this->operatorModeExpected();
            $this->pointerToBlocking();
            if ($this->pointer === null || !$this->pointer->isParenthesis()) {
                throw new ParserException("closing parenthesis without opening");
            }
            if (!in_array($this->pointer->id(), $ids)) {
                throw new ParserException("closing parenthesis does not match opening");
            }
        }
        switch ($this->pointer->getParenthesisType()) {
            case 'value':
            case 'postfix':
                $this->valueMode = false;
                break;
            case 'prefix':
                if ($emptyPar) {
                    $this->pointer->setEmptyPrefixIndex();
                }
                $this->valueMode = true;
                break;
        }
    }

    public function pushConstant($constant)
    {
        $this->valueModeExpected();
        $expr = ProtoExpression::createConstant($constant);
        $this->connect($expr);
        $this->valueMode = false;
    }

    public function pushVariable($name)
    {
        $this->valueModeExpected();
        $expr = ProtoExpression::createVariable($name);
        $this->connect($expr);
        $this->valueMode = false;
    }

    public function getExpression()
    {
        $this->operatorModeExpected();
        $this->pointerToBlocking();
        if ($this->pointer !== null) {
            throw new ParserException("unclosed operator/parenthesis");
        }
        return $this->expr;
    }

    private function pointerMoveUpToPrio($prio)
    {
        if ($this->pointer === null) {
            return false;
        }
        $parent = $this->pointer->parent();
        if ($parent === null) {
            return false;
        }
        if ($parent->isBlocking()) {
            return false;
        }
        $p = $parent->getPriority();
        if ($p < $prio) {
            return false;
        }
        if ($p == $prio && $parent->getArity() != "l") {
            return false;
        }
        $this->pointer = $parent;
        return true;
    }

    private function pointerToPriority($prio)
    {
        while ($this->pointerMoveUpToPrio($prio)) {
        }
    }

    private function pointerToBlocking()
    {
        if ($this->pointer === null) {
            return;
        }
        do {
            $this->pointer = $this->pointer->parent();
        } while ($this->pointer !== null && !$this->pointer->isBlocking());
    }

    private function connect($expr)
    {
        if ($this->pointer === null) {
            $this->expr = $expr;
            $expr->setParent(null);
            $this->pointer = $expr;
        } else {
            $this->pointer->addSubexpression($expr);
            $this->pointer = $expr;
        }
    }

    private function replace($newExpr)
    {
        if ($this->pointer === null) {
            throw new ParserException("bug"); //should never happen
        }
        $current = $this->pointer;
        $parent = $this->pointer->parent();
        $current->setParent(null);
        if ($parent !== null) {
            $parent->replaceLast($newExpr);
        } else {
            $newExpr->setParent(null);
            $this->expr = $newExpr;
        }
        return $current;
    }

    private function valueModeExpected()
    {
        if (!$this->valueMode) {
            throw new ParserException("expecting some operator/postfix symbol, but got a value/prefix");
        }
    }

    private function operatorModeExpected()
    {
        if ($this->valueMode) {
            throw new ParserException("expecting some value/prefix symbol, but got an operator/postfix");
        }
    }
}
