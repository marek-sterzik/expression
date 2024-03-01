#!/usr/bin/php
<?php

require_once(__DIR__."/../vendor/autoload.php");

use Sterzik\Expression\Parser;

$parser = new Parser();

#quotes are part of the string, backslash must be double encoded
#because of the php string syntax
$expr = $parser->parse('"Hello world!\\n"');

echo $expr->evaluate();

