<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use Sterzik\Expression\Evaluator;
use Sterzik\Expression\Variables;

use Exception;

final class ResultTest extends TestCase
{
    private array $evaluators = [];

    public static function getResultCases(): array
    {
        return [
            [null, "1+1", 2],
            [null, "1-1", 0],
            [null, "-1", -1],
            [null, "+-1", -1],
            [null, "true", true],
            [null, "True", true],
            [null, "!true", false],
            [null, "!false", true],
            [null, "2*2", 4],
            [null, "8/2", 4],
            [null, "15%7", 1],
            [null, "15>7", true],
            [null, "15>15", false],
            [null, "7>15", false],
            [null, "15>=7", true],
            [null, "15>=15", true],
            [null, "7>=15", false],
            [null, "15<7", false],
            [null, "15<15", false],
            [null, "7<15", true],
            [null, "15<=7", false],
            [null, "15<=15", true],
            [null, "7<=15", true],
            [null, "1==2", false],
            [null, "2==2", true],
            [null, "1!=2", true],
            [null, "2!=2", false],
            [null, "true && 5", true],
            [null, "false && 5", false],
            [null, "false && false", false],
            [null, "true || 5", true],
            [null, "false || 5", true],
            [null, "false || false", false],
            [null, "true?1:2", 1],
            [null, "false?1:2", 2],
            [null, "a=2", 2],

            ["empty", "a", 2, ["a" => 2]],
            ["empty", "a", null, []],

            #lvalue testing
            [null, "a=2", 2, [], ["a" => 2]],

            ["ev1", "1+2", "binary:1+2"],
            ["ev1", "+2", "unary:2"],
            ["ev2", "1+2", "binary:1+2"],
            ["ev2", "+2", "unary:2"],
            ["ev3", "1+2", "binary:1+2"],
            ["ev3", "+2", "unary:2"],
            ["ev4", "1+2", "binary:1+2"],
            ["ev4", "+2", "unary:2"],
        ];
    }

    #[DataProvider('getResultCases')]
    public function testResult(?string $evaluator, string $expr, $expectedResult, array $vars = [], ?array $checkVars = null): void
    {
        if ($evaluator !== null) {
            $evaluator = $this->getEvaluator($evaluator);
        }
        $this->doTestResult($evaluator, $expr, $expectedResult, $vars, $checkVars);
    }

    private function doTestResult(?Evaluator $evaluator, string $expr, $result, array $vars = [], ?array $checkVars = null)
    {
        $parsed = TestHelper::parse($expr);
        $this->assertNotNull($parsed);
        
        $vars = new Variables($vars);
        $res = $parsed->evaluate($vars, $evaluator);
        $vars = $vars->asArray();

        $this->assertSame($res, $result);
        
        if ($checkVars !== null) {
            ksort($checkVars);
            ksort($vars);
            $this->assertSame($checkVars, $vars);
        }
    }

    private function getEvaluator(string $name)
    {
        if (!isset($this->evaluators[$name])) {
            if ($name === "") {
                throw new Exception("Evaluator's name cannot be empty");
            }
            $method = "createEvaluator" . ucfirst($name);
            if (!is_callable([$this, $method])) {
                throw new Exception("Unknown evaluator: $name");
            }
            $this->evaluators[$name] = $this->$method();
        }
        return $this->evaluators[$name];
    }

    private function createEvaluatorEmpty()
    {
        return Evaluator::get("createEmpty");
    }

    private function createEvaluatorEv1()
    {
        $evaluator = Evaluator::get("createEmpty");
        $evaluator->defOp("+", function ($a, $b) {
            return "binary:$a+$b";
        });
        $evaluator->defOp("+", function ($a) {
            return "unary:$a";
        });
        return $evaluator;
    }

    private function createEvaluatorEv2()
    {
        $evaluator = Evaluator::get("createEmpty");
        $evaluator->defOp("+", function ($op, $a, $b) {
            return "binary:$a+$b";
        }, true);
        $evaluator->defOp("+", function ($a) {
            return "unary:$a";
        });
        return $evaluator;
    }

    private function createEvaluatorEv3()
    {
        $evaluator = Evaluator::get("createEmpty");
        $evaluator->defOp("+", function ($a, $b) {
            return "binary:$a+$b";
        });
        $evaluator->defOp("+", function ($op, $a) {
            return "unary:$a";
        }, true);
        return $evaluator;
    }

    private function createEvaluatorEv4()
    {
        $evaluator = Evaluator::get("createEmpty");
        $evaluator->defOp("+", function ($op, $a, $b) {
            return "binary:$a+$b";
        }, true);
        $evaluator->defOp("+", function ($op, $a) {
            return "unary:$a";
        }, true);
        return $evaluator;
    }
}
