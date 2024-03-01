<?php

namespace Sterzik\Expression;

/*
 * This class represents any expression. An expression
 * is represented by the abstract evaluation tree, so
 * any instance of this class represents one node
 * of that evaluation tree. The root node represents
 * the whole expression.
 */
abstract class Expression
{
    protected static $subClassShortcuts = [
        "OperationExpression" => "op",
        "VariableExpression" => "var",
        "ConstantExpression" => "const",
    ];
    protected static $shortcutClasses = null;


    /*
     * Creates a new expression of the given type with proper arguments
     */
    public static function create($type, ...$args)
    {
        $class = static::classForType($type);
        if ($class === null) {
            return null;
        }
        return new $class(...$args);
    }

    /*
     * Restore expression from dumped (non-object) php structure
     */
    public static function restore($dumped)
    {
        if (!is_array($dumped) || array_keys($dumped) !== range(0, count($dumped) - 1)) {
            return null;
        }
        $class = static::classForType(array_shift($dumped));
        if ($class === null) {
            return null;
        }
        return call_user_func([$class,"restoreExpression"], $dumped);
    }

    /*
     * Restore expression from dumped json
     */
    public static function restoreJson($dumped)
    {
        if (!is_string($dumped)) {
            return null;
        }
        $dumped = json_decode($dumped, true);
        if ($dumped === false) {
            return null;
        }
        return static::restore($dumped);
    }

    /*
     * Restore expression from dumped base64
     */
    public static function restoreBase64($dumped)
    {
        if (!is_string($dumped)) {
            return null;
        }
        return static::restoreJson(base64_decode($dumped));
    }

    /*
     * dump the whole expression as a php structure
     */
    public function dump()
    {
        return array_merge([$this->type()], $this->dumpExpression());
    }

    /*
     * dump the whole expression as json
     * note: will not work, if you are using object constants
     */
    public function dumpJson()
    {
        return json_encode($this->dump());
    }

    /*
     * dump the whole expression as base64
     * note: will not work, if you are using object constants
     */
    public function dumpBase64()
    {
        return base64_encode($this->dumpJson());
    }

    /*
     * Returns the type of the expression. Possible values:
     * - "op"    - operation
     * - "var"   - variable
     * - "const" - constant
     */
    public function type()
    {
        $class = basename(str_replace('\\', '/', get_class($this)));
        if (isset(static::$subClassShortcuts[$class])) {
            return static::$subClassShortcuts[$class];
        } else {
            return $class;
        }
    }

    /*
     * Evaluate the expression by the given variable object
     * and evaluator.
     */
    public function evaluate($variables = null, $evaluator = null)
    {
        $evaluator = Evaluator::get($evaluator);
        $ret = $this->evaluateExpression($variables, $evaluator);
        if (is_a($ret, __NAMESPACE__ . '\\LValue')) {
            $ret = $ret->value();
        }
        return $ret;
    }

    /*
     * Postprocess the expression by the given postprocessor.
     */
    public function postprocess($postprocessor = null)
    {
        $postprocessor = Postprocessor::get($postprocessor);
        return $this->postprocessExpression($postprocessor);
    }


    private static function classForType($type)
    {
        $class = $type;
        if (static::$shortcutClasses === null) {
            static::$shortcutClasses = array_flip(static::$subClassShortcuts);
        }
        if (isset(static::$shortcutClasses[$class])) {
            $class = static::$shortcutClasses[$class];
        }
        $class = __NAMESPACE__ . '\\' . $class;
        if (!is_a($class, __CLASS__, true)) {
            return null;
        }
        return $class;
    }

    abstract protected static function restoreExpression($data);
    abstract protected function dumpExpression();
    abstract protected function evaluateExpression($variables, $evaluator);
    abstract protected function postprocessExpression($postprocessor);
}
