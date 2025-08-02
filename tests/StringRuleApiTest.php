<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Api\StringRuleApi;
use PHPUnit\Framework\TestCase;

final class StringRuleApiTest extends TestCase
{
    public function testEvaluateSimpleExpression(): void
    {
        $expr = '.a > 1 and .b < 3';
        $data = ['a' => 2, 'b' => 2];

        self::assertTrue(StringRuleApi::evaluate($expr, $data));
    }

    public function testEvaluateNestedExpression(): void
    {
        $expr = '(.actualAge > 18 or .name is Adam) or (.citizenship is PL and .actualAge > 15)';
        $data = ['actualAge' => 16, 'name' => 'John', 'citizenship' => 'PL'];

        self::assertTrue(StringRuleApi::evaluate($expr, $data));
    }

    public function testEvaluateNotOperator(): void
    {
        $expr = '.a is 1 and not (.b < 2)';
        $data = ['a' => 1, 'b' => 3];

        self::assertTrue(StringRuleApi::evaluate($expr, $data));
    }

    public function testEvaluateRuleset(): void
    {
        $expressions = [
            'age' => '.age > 18',
            'name' => '.name is John',
        ];
        $data = ['age' => 20, 'name' => 'John'];

        self::assertTrue(StringRuleApi::evaluate($expressions, $data));
        self::assertFalse(StringRuleApi::evaluate($expressions, ['age' => 20, 'name' => 'Jane']));
    }

    public function testEvaluateDeeplyNestedExpression(): void
    {
        $expr = '((.a > 1 and (.b < 3 or .c is 2)) or ((.d >= 5 and .e <= 10) and not (.f != 7))) and (.g is true or .h is false)';
        $data = [
            'a' => 2,
            'b' => 2,
            'c' => 2,
            'd' => 5,
            'e' => 10,
            'f' => 7,
            'g' => true,
            'h' => true,
        ];

        self::assertTrue(StringRuleApi::evaluate($expr, $data));
    }

    public function testEvaluateVeryComplexExpression(): void
    {
        $expr = '((.a > 5 and (.b < 3 or (.c is 2 and (.d > 4 or (.e < 5 and .f is 6))))) or (.g <= 7 and (.h != 8 or (.i >= 9 and .j <= 10)))) and not (.k)';
        $data = [
            'a' => 2,
            'b' => 2,
            'c' => 2,
            'd' => 5,
            'e' => 4,
            'f' => 6,
            'g' => 7,
            'h' => 8,
            'i' => 10,
            'j' => 9,
            'k' => true,
        ];

        self::assertFalse(StringRuleApi::evaluate($expr, $data));
    }

    public function testEvaluateBooleanVariablesWithoutExplicitComparison(): void
    {
        $expr = '.g and .x';
        $data = ['g' => true, 'x' => true];

        self::assertTrue(StringRuleApi::evaluate($expr, $data));
        self::assertFalse(StringRuleApi::evaluate($expr, ['g' => true, 'x' => false]));
    }

    public function testEvaluateNotOnBooleanVariable(): void
    {
        $expr = 'not .g';
        $data = ['g' => false];

        self::assertTrue(StringRuleApi::evaluate($expr, $data));
        self::assertFalse(StringRuleApi::evaluate($expr, ['g' => true]));
    }
}
