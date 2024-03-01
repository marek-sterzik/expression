<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use Sterzik\Expression\Evaluator;
use Sterzik\Expression\Parser;
use Sterzik\Expression\Expression;

use Exception;

final class DumpRestoreTest extends TestCase
{
    public function testDumpRestore(): void
    {
        $parsed = TestHelper::parse("a+b*c+d==e");
        $strShouldBe = "==(+(+(a,*(b,c)),d),e)";
        $structure = TestHelper::structure($parsed);
        $this->assertSame($strShouldBe, $structure);
        $dump = $parsed->dumpBase64();
        $restored = Expression::restoreBase64($dump);
        $this->assertNotNull($restored);
        $structure = TestHelper::structure($restored);
        $this->assertSame($strShouldBe, $structure);
    }
}
