<?php
require_once __DIR__."/../vendor/autoload.php";

use Tests\TestHelper;
use Sterzik\Expression\Evaluator;
use Sterzik\Expression\Parser;
use Sterzik\Expression\Variables;

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

