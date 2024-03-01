<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use Sterzik\Expression\Evaluator;
use Sterzik\Expression\Parser;
use Sterzik\Expression\Expression;

use Exception;

final class ExceptionGenerationTest extends TestCase
{
    public function testExceptionGenerationEnabled(): void
    {
        $invalid = "$$ invalid @#$!! \" expression";
        $parser = new Parser(null);
        $parser->throwExceptions();
        $this->expectException(Exception::class);
        $expr = $parser->parse($invalid);
    }

    public function testExceptionGenerationDisabled(): void
    {
        $invalid = "$$ invalid @#$!! \" expression";
        $parser = new Parser(null);
        $parser->throwExceptions(false);
        $expr = $parser->parse($invalid);
        $this->assertNull($expr);
    }
}

