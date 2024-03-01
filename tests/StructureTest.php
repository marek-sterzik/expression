<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use SPSOstrov\GetOpt\OptionParser;
use SPSOstrov\GetOpt\Option;
use SPSOstrov\GetOpt\ParserException;


final class StructureTest extends TestCase
{

    public static function getStructureCases(): array
    {
        return [
            ["a&b", "&&(a,b)"],
            ["a & not b", "&&(a,!(b))"],
            ["a + b * c", "+(a,*(b,c))"],
            ["a + b * c + d", "+(+(a,*(b,c)),d)"],
            ["a * b + c", "+(*(a,b),c)"],
            ["a + b + c", "+(+(a,b),c)"],
            ["a = b = c", "=(a,=(b,c))"],
            ["a == b? c + d : e * f", "?:(==(a,b),+(c,d),*(e,f))"],
            ["a + b * c + d", "+(+(a,*(b,c)),d)"],
            ["a - b", "-(a,b)"],
            ["- b", "-(b)"],
            ["a - - b", "-(a,-(b))"],
            ["a - - b ? x : y", "?:(-(a,-(b)),x,y)"],
            ["a,b,c", ",(a,b,c)"],
            ["+-a", "+(-(a))"],
            ["a?b:c?d:e", "?:(a,b,?:(c,d,e))"],
            ["a?b?c:d:e", "?:(a,?:(b,c,d),e)"],
            ["a?b=c:d", "?:(a,=(b,c),d)"],

            ["(a)", "a"],
            ["(a", null],
            ["a)", null],
            ["[(a)]", "[](a)"],
            ["([a])", "[](a)"],
            ["[a,b,c]", "[](a,b,c)"],
            ["[a,(b,c),d]", "[](a,,(b,c),d)"],
            ["a(b)", "fn()(a,b)"],
            ["a(b,c)", "fn()(a,b,c)"],
            ["a()", "fn()(a)"],

            ["(a,b)(c)", "fn()(,(a,b),c)"],
            ["(a,b)(c,d)", "fn()(,(a,b),c,d)"],
            ["(a,b)()", "fn()(,(a,b))"],
        ];
    }

    #[DataProvider('getStructureCases')]
    public function testStructure(string $expr, ?string $pattern): void
    {
        $this->doTestStructure(null, $expr, $pattern);
    }

    private function doTestStructure($ps, string $expr, ?string $pattern)
    {
        $parsed = TestHelper::parse($expr, $ps);
        if ($pattern === null) {
            $this->assertSame($pattern, $parsed);
        } else {
            $this->assertNotNull($parsed);
            $res = TestHelper::structure($parsed);
            $this->assertSame($res, $pattern);
        }
    }
}
