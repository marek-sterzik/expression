<?php

namespace Sterzik\Expression\Priv;

use Exception;
use Sterzik\Expression\Expression;

/*
 * This class is used for temporary representation of the expression.
 * It is for internal use only.
 */
class ProtoExpression
{
    private $type;
    private $data;
    private $subExpressions;
    private $parent;

    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
        $this->subExpressions = [];
        $this->parent = null;
    }

    public function parent()
    {
        return $this->parent;
    }

    public function toExpression()
    {
        switch ($this->type) {
            case 'operator':
            case 'variadic':
            case 'prefix':
            case 'postfix':
            case 'par-value':
            case 'par-postfix':
            case 'par-prefix':
                $subs = [];
                foreach ($this->subExpressions as $p) {
                    $subs[] = $p->toExpression();
                }
                if ($this->type == "par-prefix" && count($subs) > 0) {
                    $sub = array_pop($subs);
                    array_unshift($subs, $sub);
                }
                return Expression::create("op", $this->data['alias'], $subs);
            case 'variable':
                return Expression::create("var", $this->data['name']);
            case 'constant':
                return Expression::create("const", $this->data['value']);
            default:
        }
    }

    public function setEmptyPrefixIndex()
    {
        $this->type = "postfix";
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function addSubexpression($expr)
    {
        $expr->setParent($this);
        $this->subExpressions[] = $expr;
    }

    public function replaceLast($expr)
    {
        $n = count($this->subExpressions);
        if ($n > 0) {
            $this->subExpressions[$n - 1] = $expr;
            $expr->setParent($this);
        }
    }

    /*parenthesis functions*/

    public function isParenthesis()
    {
        if (substr($this->type, 0, 4) == "par-") {
            return true;
        } else {
            return false;
        }
    }

    public function allowEmpty()
    {
        return $this->data['empty'];
    }


    /*return: value, prefix, postfix*/
    public function getParenthesisType()
    {
        if (substr($this->type, 0, 4) == "par-") {
            return substr($this->type, 4);
        } else {
            return null;
        }
    }

    public function id()
    {
        return isset($this->data['id']) ? $this->data['id'] : null;
    }

    public function getNumSubexpressions()
    {
        return count($this->subExpressions);
    }

    public function isBlocking()
    {
        switch ($this->type) {
            case 'operator':
                if ($this->data['parts'] >= count($this->subExpressions)) {
                    return true;
                }
                return false;
            case 'par-value':
            case 'par-postfix':
                return true;
            case 'par-prefix':
                if (count($this->subExpressions) < 2) {
                    return true;
                }
                return false;
            default:
                return false;
        }
    }

    public function getPriority()
    {
        return isset($this->data['priority']) ? $this->data['priority'] : null;
    }

    public function getArity()
    {
        return isset($this->data['arity']) ? $this->data['arity'] : null;
    }

    public static function createPrefix($alias, $priority, $arity)
    {
        return new self(
            "prefix",
            ["alias" => $alias, "priority" => $priority, "arity" => $arity]
        );
    }

    public static function createPostfix($alias, $priority)
    {
        return new self(
            "postfix",
            ["alias" => $alias,"priority" => $priority]
        );
    }

    public static function createOperator($id, $alias, $priority, $arity, $n)
    {
        return new self(
            "operator",
            ["id" => $id, "alias" => $alias,"priority" => $priority, "arity" => $arity, "parts" => $n]
        );
    }

    public static function createVariadicOperator($id, $alias, $priority, $arity)
    {
        return new self(
            "variadic",
            ["id" => $id, "alias" => $alias,"priority" => $priority, "arity" => $arity]
        );
    }

    public static function createParenthesis($id, $alias, $allowEmpty)
    {
        return new self(
            "par-value",
            ["id" => $id, "alias" => $alias, "empty" => $allowEmpty]
        );
    }

    public static function createPrefixIndex($id, $alias, $priority, $arity, $allowEmpty)
    {
        return new self(
            "par-prefix",
            ["id" => $id, "alias" => $alias, "priority" => $priority, "arity" => $arity, "empty" => $allowEmpty]
        );
    }

    public static function createPostfixIndex($id, $alias, $priority, $allowEmpty)
    {
        return new self(
            "par-postfix",
            ["id" => $id, "alias" => $alias, "priority" => $priority, "empty" => $allowEmpty]
        );
    }

    public static function createVariable($name)
    {
        return new self(
            "variable",
            ["name" => $name]
        );
    }

    public static function createConstant($constant)
    {
        return new self(
            "constant",
            ["value" => $constant]
        );
    }
}
