<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use Sterzik\Expression\Evaluator;
use Sterzik\Expression\Parser;

use Exception;

final class LValueTest extends TestCase
{
    public function testDefNotLValue(): void
    {
        $ep = Evaluator::get("default");
        $ep->defNotLValue(function () {
            throw new Exception("Not an LValue");
        });
        $parser = new Parser();
        $parsed = $parser->parse("2=2");
        $this->assertNotNull($parsed);

        $this->expectException(Exception::class);
        $parsed->evaluate(null, $ep);
    }
    
    public function testLValueHandlerDoesGetFunctionName(): void
    {
        $triggered = false;
        $tfun = null;
        $targs = null;
        $parser = new Parser();
        $evaluator = Evaluator::get("createEmpty");
        $evaluator->defOpEx("=", function ($a, $b) {
            $a->assign($b->value());
        });
        $evaluator->defNotLvalue(function ($fun, ...$args) use (&$triggered, &$tfun, &$targs) {
            $triggered = true;
            $tfun = $fun;
            $targs = $args;
        });

        $parser->parse("3=3")->evaluate(null, $evaluator);

        $this->assertTrue($triggered);
        $this->assertSame('assign', $tfun);
        $this->assertIsArray($targs);
        $this->assertCount(1, $targs);
        $this->assertSame(3, $targs[0]);
    }
}
