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

#test proper matching of function types


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
