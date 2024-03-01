#!/usr/bin/php
<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Sterzik\Expression\Parser;
use Sterzik\Expression\ParserSettings;
use Sterzik\Expression\Expression;
use Sterzik\Expression\Evaluator;

###############################################################################
#basic parsing:

$parser = new Parser();
$expr = $parser->parse("1+2*3");


###############################################################################
#basic evaluation:

$result = $expr->evaluate();
echo "result(should be 7): $result\n"; //7


###############################################################################
#using variables:

$expr = $parser->parse("a+b*(c+d)");
$vars = ["a" => 1, "b" => 2, "c" => 3, "d" => 4];

$result = $expr->evaluate($vars);

echo "result(should be 15): $result\n"; //15


###############################################################################
#building custom parser:

$ps = ParserSettings::get("empty");

//both operations have the same priority and arity:
$ps->opPriority(1)->opArity("l");
$ps->addBinaryOp("+")->addBinaryOp("*");

$parser = new Parser($ps);

$expr = $parser->parse("1+2*3");

$result = $expr->evaluate();

echo "result(should be 9): $result\n"; //9


###############################################################################
#making the custom parser as default:

$tmp = $ps->setDefault(); //store the old settings to be able to restore it
$parser = new Parser();
$expr = $parser->parse("1+2*3");
$result = $expr->evaluate();

echo "result(should be 9): $result\n"; //9


###############################################################################
#going back to the original default parser:

$tmp->setDefault();
$parser = new Parser();
$expr = $parser->parse("1+2*3");
$result = $expr->evaluate();

echo "result(should be 7): $result\n"; //7


###############################################################################
#dumping and restoring already parsed expressions:

//dumps as php structure without objects (except constants)
$data = $expr->dump();
echo "serialized data:\n";
vardumpExpression($data);
$expr2 = Expression::restore($data);
$result = $expr2->evaluate();
echo "result(should be 7): $result\n"; //7

//dumps as json (works only if all constants are serializable to json)
$data = $expr->dumpJson();
echo "serialized data:\n";
vardumpExpression($data);
$expr2 = Expression::restoreJson($data);
$result = $expr2->evaluate();
echo "result(should be 7): $result\n"; //7

//dumps as json (works only if all constants are serializable to json)
$data = $expr->dumpBase64();
echo "serialized data:\n";
vardumpExpression($data);
$expr2 = Expression::restoreBase64($data);
$result = $expr2->evaluate();
echo "result(should be 7): $result\n"; //7


###############################################################################
#creating a custom expression evaluator:

$ev = Evaluator::get("empty");
$ev->defOp("+", function ($a, $b) {
    return $a.$b;
});

$parser = new Parser();
$expr = $parser->parse("1+2");
$result = $expr->evaluate(null, $ev); //NULL - we don't need variables

echo "result (should be 12): $result\n";
?>
