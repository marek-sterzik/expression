<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use Sterzik\Expression\ParserSettings;
use Sterzik\Expression\Parser;

final class StructureTest extends TestCase
{
    private $specialParser = null;

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

    public static function getSpecialStructureCases(): array
    {
        return [
            ["++a + b++", "++(+(++(a),b))"],
            ["--a - b--", "--(-(a,--(b)))"],

            #mixing parenthesis of different types together while sharing the end parenthesis
            ["begin a end", "be1(a)"],
            ["beg a end", "be2(a)"],
            ["BEGIN a END", null],
            ["BEG a END", "be2(a)"],

            #testing the prefix index parser
            ["<a>b", "<>(b,a)"],
            ["<>b", "<>(b)"],
            ["<<a>>b", "<<>>(b,a)"],
            ["<<>>b", null],
        ];
    }

    #[DataProvider('getStructureCases')]
    public function testStructure(string $expr, ?string $pattern): void
    {
        $this->doTestStructure(null, $expr, $pattern);
    }

    #[DataProvider('getSpecialStructureCases')]
    public function testSpecialParserStructure(string $expr, ?string $pattern): void
    {
        $this->doTestStructure($this->getSpecialParserSettings(), $expr, $pattern);
    }

    public function testParserSettingsInterchange(): void
    {
        $specialParserSettings = $this->getSpecialParserSettings();
        $this->doTestStructure(null, "<<a>>b", null);
        $tmpParserSettings = $specialParserSettings->setDefault();
        $this->doTestStructure(null, "<<a>>b", "<<>>(b,a)");
        $tmpParserSettings->setDefault();
        $this->doTestStructure(null, "<<a>>b", null);
    }

    public function testStructureFromFileDescriptor(): void
    {
        $fd = fopen(__DIR__."/expression.tst", "r");
        $this->doTestStructure(null, $fd, "+(1,*(2,3))");
        fclose($fd);
    }

    private function doTestStructure($ps, $expr, ?string $pattern)
    {
        $parsed = TestHelper::parse($expr, $ps);
        if ($pattern === null) {
            $this->assertSame($pattern, $parsed);
        } else {
            $this->assertNotNull($parsed);
            $res = TestHelper::structure($parsed);
            $this->assertSame($pattern, $res);
        }
    }

    private function getSpecialParserSettings(): ParserSettings
    {
        if ($this->specialParser === null) {
            $this->specialParser = ParserSettings::get('createEmpty');
            $this->specialParser->opArity(">")->opPriority(1);
            $this->specialParser->addBinaryOp("+")->addPrefixOp("++")->addPostfixOp("++");
            $this->specialParser->opArity("<")->opPriority(2);
            $this->specialParser->addBinaryOp("-")->addPrefixOp("--")->addPostfixOp("--");
            $this->specialParser->caseSensitive(true);
            $this->specialParser->addParenthesis("be1", "begin", "end", false);
            $this->specialParser->caseSensitive(false);
            $this->specialParser->addParenthesis("be2", "beg", "end", true);
            $this->specialParser->addPrefixIndex("<>", "<", ">", true);
            $this->specialParser->addPrefixIndex("<<>>", "<<", ">>", false);
        }
        return $this->specialParser;
    }
}
