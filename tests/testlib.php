<?php
require_once __DIR__."/../vendor/autoload.php";

use Sterzik\Expression\Evaluator;
use Sterzik\Expression\Parser;
use Sterzik\Expression\Variables;

class TestHelper
{
    public static function getStructureEvaluator()
    {
        $ev = new Evaluator();
        $ev->defVar(function ($var) {
            return $var;
        });
        $ev->defConst(function ($const) {
            return json_encode($const);
        });
        $ev->defOpDefault(function (...$args) {
            $op = array_shift($args);
            return $op."(".implode(",", $args).")";
        });
        return $ev;
    }

    public static function parse($expr, $parserSettings = null)
    {
        $parser = new Parser($parserSettings);
        return $parser->parse($expr);
    }

    public static function structure($expr)
    {
        if ($expr === null) {
            return null;
        }
        return $expr->evaluate(null, static::getStructureEvaluator());
    }

    public static function variablesEq($varA, $varB)
    {
        if (is_array($varA)) {
            ksort($varA);
        }
        if (is_array($varB)) {
            ksort($varB);
        }

        return $varA === $varB;
    }
}



function testStructure($expr, $pattern)
{
    testStructureEx(null, $expr, $pattern);
}

function testStructureEx($ps, $expr, $pattern)
{
    Test::run(function () use ($ps, $expr, $pattern) {
        $parsed = TestHelper::parse($expr, $ps);
        if ($pattern === null) {
            $pattern = false;
        }
        if ($pattern === false) {
            if ($parsed === null) {
                return true;
            } else {
                echo "Test failed: Expression '$expr' should not be parsable, but it is.\n";
                return false;
            }
        } else {
            if ($parsed === null) {
                echo "Test failed: Expression '$expr' should be parsable, but it is not.\n";
                return false;
            } else {
                $res = TestHelper::structure($parsed);
                if ($res == $pattern) {
                    return true;
                } else {
                    echo "Test failed: Pattern of '$expr' should be '$pattern', but is '$res'.\n";
                    return false;
                }
            }
        }
    });
}

function testResultEx($evaluator, $expr, $result, $vars = [], $checkVars = null)
{
    Test::run(function () use ($evaluator, $expr, $result, $vars, $checkVars) {
        $parsed = TestHelper::parse($expr);
        if ($parsed === null) {
            echo "Test failed: Cannot compile expression '$expr'\n";
            return false;
        }
        $vars = new Variables($vars);
        $res = $parsed->evaluate($vars, $evaluator);
        $vars = $vars->asArray();
        if ($res === $result) {
            if ($checkVars !== null) {
                if (!TestHelper::variablesEq($vars, $checkVars)) {
                    $shouldBe = json_encode($checkVars);
                    $is = json_encode($vars);
                    echo "Test failed: variables not match: should be: $shouldBe but is: $is\n";
                    return false;
                }
            }
            return true;
        } else {
            $shouldBe = json_encode($result);
            $reallyIs = json_encode($res);
            echo "Test failed: Pattern of '$expr' failed. Should be: $shouldBe, but is: $reallyIs\n";
            return false;
        }
    });
}

class Test
{
    public static $ok;
    public static $count;
    public static $time;
    public static $ignoreTest;

    public static function begin(bool $ignoreTest = false)
    {
        static::$ok = 0;
        static::$count = 0;
        static::$time = microtime(true);
        static::$ignoreTest = $ignoreTest;
        set_exception_handler(function ($e) {
            static::doFinish($e);
        });
    }

    public static function finish()
    {
        return static::doFinish(null);
    }

    private static function doFinish($exceptionRaised)
    {
        $time = microtime(true) - static::$time;
        if ($time < 2) {
            $time = round($time*1000, 1)."ms";
        } else {
            $time = round($time, 3)."s";
        }
        if (static::$ok == static::$count && $exceptionRaised === null) {
            echo "All ".static::$count." test".((static::$count==1)?'':'s')." OK. Running time: ${time}\n";
            exit(0);
        } else {
            $failed = static::$count-static::$ok;
            echo "Error: $failed/".static::$count." tests failed";
            if ($exceptionRaised !== null) {
                echo ", ";
                if (static::$ok == static::$count) {
                    echo "but";
                } else {
                    echo "and";
                }
                echo " the test ended up with an exception:\n";
                echo $exceptionRaised;
            }
            echo ".\n";
            exit(static::$ignoreTest?0:1);
        }
    }

    private static function output($output)
    {
        if ($output != "") {
            $last = $output[strlen($output)-1];
            echo $output;
            if ($last != "\n") {
                echo "\n";
            }
        }
    }

    public static function run()
    {
        static::$count++;
        $arguments = func_get_args();
        $function = array_shift($arguments);
        ob_start();
        if ($function !== null && is_callable($function)) {
            try {
                $rv = call_user_func_array($function, $arguments)?true:false;
            } catch (Exception $e) {
                echo "Test failed: Exception raised: ".get_class($e)." \"".$e->getMessage()."\"\n";
                $rv = false;
            }
        } else {
            $rv = false;
            echo "Test failed: No such a function.\n";
        }
        $output = ob_get_contents();
        ob_end_clean();
        if ($rv) {
            static::$ok++;
        } else {
            static::output($output);
        }
        return $rv;
    }
}

function testResult($expr, $result, $vars = [], $checkVars = null)
{
    testResultEx(null, $expr, $result, $vars, $checkVars);
}
