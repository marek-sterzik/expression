<?php

namespace Sterzik\Expression\Priv;

use Sterzik\Expression\ParserSettings;

/*
 * This class is a builder for ParserSettings presets.
 * Currently there are two presets:
 *  1. default - creates a default parser settings with standard operations
 *  2. empty - creates an empty parser settings used to create new ones
 */
class ParserPreset extends AbstractPreset
{
    public static function default()
    {
        $ps = static::createEmpty();
        $ps->caseSensitive(false);
        $ps->opArity('>');
        $ps->opPriority(1);
            $ps->addVariadicOp(",");
        $ps->opArity('<');
        $ps->opPriority(2);
            $ps->addBinaryOp("=");
        $ps->opArity('<');
        $ps->opPriority(3);
            $ps->addMultinaryOp("?:", "?", ":");
        $ps->opArity('>');
        $ps->opPriority(4);
            $ps->addBinaryOp("||", "or");
            $ps->addBinaryOp("||", "|");
            $ps->addBinaryOp("||");
            $ps->addBinaryOp("&&", "and");
            $ps->addBinaryOp("&&", "&");
            $ps->addBinaryOp("&&");
        $ps->opPriority(5);
            $ps->addBinaryOp("==");
            $ps->addBinaryOp("!=", "<>");
            $ps->addBinaryOp("!=");
        $ps->opPriority(6);
            $ps->addBinaryOp(">");
            $ps->addBinaryOp("<");
            $ps->addBinaryOp(">=");
            $ps->addBinaryOp("<=");
        $ps->opPriority(7);
            $ps->addBinaryOp("+");
            $ps->addBinaryOp("-");
        $ps->opPriority(8);
            $ps->addBinaryOp("*");
            $ps->addBinaryOp("/");
            $ps->addBinaryOp("%");
        $ps->opPriority(9);
            $ps->addPrefixOp("!", "not");
            $ps->addPrefixOp('!', '~');
            $ps->addPrefixOp('!');
            $ps->addPrefixOp('-');
            $ps->addPrefixOp('+');

        $ps->addParenthesis("()", '(', ')', false);
        $ps->addParenthesis("[]", '[', ']', true);

        $ps->addPostfixIndex("fn()", '(', ')', true);

        $ps->addConstant("true", true);
        $ps->addConstant("false", false);

        $ps->setPostprocessor("default");
        return $ps;
    }

    public static function createEmpty()
    {
        return new ParserSettings();
    }
}
