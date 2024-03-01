#!/usr/bin/php
<?php
require_once __DIR__."/testlib.php";

use Tests\TestHelper;
use Sterzik\Expression\ParserSettings;
use Sterzik\Expression\Parser;
use Sterzik\Expression\Evaluator;
use Sterzik\Expression\Expression;

use Sterzik\Expression\ParserException;

Test::begin();
$fd = fopen(__DIR__."/expression.tst", "r");
testStructure($fd, "+(1,*(2,3))");
fclose($fd);

$sps = ParserSettings::get('createEmpty');
$sps->opArity(">")->opPriority(1);
$sps->addBinaryOp("+")->addPrefixOp("++")->addPostfixOp("++");
$sps->opArity("<")->opPriority(2);
$sps->addBinaryOp("-")->addPrefixOp("--")->addPostfixOp("--");
$sps->caseSensitive(true);
$sps->addParenthesis("be1", "begin", "end", false);
$sps->caseSensitive(false);
$sps->addParenthesis("be2", "beg", "end", true);
$sps->addPrefixIndex("<>", "<", ">", true);
$sps->addPrefixIndex("<<>>", "<<", ">>", false);

testStructureEx($sps, "++a + b++", "++(+(++(a),b))");
testStructureEx($sps, "--a - b--", "--(-(a,--(b)))");

#mixing parenthesis of different types together while sharing the end parenthesis
testStructureEx($sps, "begin a end", "be1(a)");
testStructureEx($sps, "beg a end", "be2(a)");
testStructureEx($sps, "BEGIN a END", null);
testStructureEx($sps, "BEG a END", "be2(a)");

#testing the prefix index parser
testStructureEx($sps, "<a>b", "<>(b,a)");
testStructureEx($sps, "<>b", "<>(b)");
testStructureEx($sps, "<<a>>b", "<<>>(b,a)");
testStructureEx($sps, "<<>>b", null);

#test of the default parser interchange
testStructure("<<a>>b", null);
$tmp = $sps->setDefault();
testStructure("<<a>>b", "<<>>(b,a)");
$tmp->setDefault();
testStructure("<<a>>b", null);

testResult("1+1", 2);
testResult("1-1", 0);
testResult("-1", -1);
testResult("+-1", -1);
testResult("true", true);
testResult("True", true);
testResult("!true", false);
testResult("!false", true);
testResult("2*2", 4);
testResult("8/2", 4);
testResult("15%7", 1);
testResult("15>7", true);
testResult("15>15", false);
testResult("7>15", false);
testResult("15>=7", true);
testResult("15>=15", true);
testResult("7>=15", false);
testResult("15<7", false);
testResult("15<15", false);
testResult("7<15", true);
testResult("15<=7", false);
testResult("15<=15", true);
testResult("7<=15", true);
testResult("1==2", false);
testResult("2==2", true);
testResult("1!=2", true);
testResult("2!=2", false);
testResult("true && 5", true);
testResult("false && 5", false);
testResult("false && false", false);
testResult("true || 5", true);
testResult("false || 5", true);
testResult("false || false", false);
testResult("true?1:2", 1);
testResult("false?1:2", 2);
testResult("a=2", 2);

#variable testing
$ee = Evaluator::get("createEmpty");
testResultEx($ee, "a", 2, ["a" => 2]);
testResultEx($ee, "a", null, []);

#lvalue testing
testResult("a=2", 2, [], ["a" => 2]);

#test proper matching of function types

$sev1 = Evaluator::get("createEmpty");
$sev1->defOp("+", function ($a, $b) {
    return "binary:$a+$b";
});
$sev1->defOp("+", function ($a) {
    return "unary:$a";
});

$sev2 = Evaluator::get("createEmpty");
$sev2->defOp("+", function ($op, $a, $b) {
    return "binary:$a+$b";
}, true);
$sev2->defOp("+", function ($a) {
    return "unary:$a";
});

$sev3 = Evaluator::get("createEmpty");
$sev3->defOp("+", function ($a, $b) {
    return "binary:$a+$b";
});
$sev3->defOp("+", function ($op, $a) {
    return "unary:$a";
}, true);

$sev4 = Evaluator::get("createEmpty");
$sev4->defOp("+", function ($op, $a, $b) {
    return "binary:$a+$b";
}, true);
$sev4->defOp("+", function ($op, $a) {
    return "unary:$a";
}, true);

testResultEx($sev1, "1+2", "binary:1+2");
testResultEx($sev1, "+2", "unary:2");
testResultEx($sev2, "1+2", "binary:1+2");
testResultEx($sev2, "+2", "unary:2");
testResultEx($sev3, "1+2", "binary:1+2");
testResultEx($sev3, "+2", "unary:2");
testResultEx($sev4, "1+2", "binary:1+2");
testResultEx($sev4, "+2", "unary:2");

#test if the Evaluator::defNotLValue() works correctly
Test::run(function () {
    $ep = Evaluator::get("default");
    $ep->defNotLValue(function () {
        throw new Exception("Not an LValue");
    });
    $parser = new Parser();
    $parsed = $parser->parse("2=2");
    if ($parsed === null) {
        return false;
    }
    try {
        $parsed->evaluate(null, $ep);
        return false;
    } catch (Exception $e) {
        return true;
    }
});

#test the dump/restore functionality
Test::run(function () {
    $parsed = TestHelper::parse("a+b*c+d==e");
    $strShouldBe = "==(+(+(a,*(b,c)),d),e)";
    $structure = TestHelper::structure($parsed);
    if ($structure != $strShouldBe) {
        return false;
    }
    $dump = $parsed->dumpBase64();
    $restored = Expression::restoreBase64($dump);
    if ($restored === null) {
        return false;
    }
    $structure = TestHelper::structure($restored);
    if ($structure != $strShouldBe) {
        return false;
    }
    return true;
});

#test proper generating of parser exceptions
Test::run(function () {
    $invalid = "$$ invalid @#$!! \" expression";
    $parser = new Parser(null);
    $parser->throwExceptions();
    try {
        $expr = $parser->parse($invalid);
        return false;
    } catch (ParserException $e) {
    }

    $parser->throwExceptions(false);
    try {
        $expr = $parser->parse($invalid);
        if ($expr !== null) {
            return false;
        }
    } catch (ParserException $e) {
        return false;
    }

    return true;
});

#test if the not-lvalue-handler does get the function name
Test::run(function () {
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

    if (!$triggered) {
        return false;
    }
    if ($tfun !== 'assign') {
        return false;
    }
    if (!is_array($targs)) {
        return false;
    }
    if (count($targs) != 1) {
        return false;
    }
    if ($targs[0] != 3) {
        return false;
    }
    return true;
});

Test::finish();

?>
