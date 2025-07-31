<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Api\JsonRule;
use PHPUnit\Framework\TestCase;

final class JsonRuleTest extends TestCase
{
    public function testEvaluateArrayRules(): void
    {
        $rules = ['and' => [
            ['<' => [['var' => 'temp'], 110]],
            ['==' => [['var' => 'pie.filling'], 'apple']],
        ]];

        $data = ['temp' => 100, 'pie' => ['filling' => 'apple']];

        self::assertTrue(JsonRule::evaluate($rules, $data));
    }

    public function testEvaluateJsonStrings(): void
    {
        $rules = ['==' => [['var' => 'a'], 1]];
        $data = ['a' => 2];

        $rulesJson = json_encode($rules, JSON_THROW_ON_ERROR);
        $dataJson = json_encode($data, JSON_THROW_ON_ERROR);

        self::assertFalse(JsonRule::evaluate($rulesJson, $dataJson));
    }

    public function testEvaluateOrOperator(): void
    {
        $rules = ['or' => [
            ['==' => [['var' => 'a'], 1]],
            ['>' => [['var' => 'b'], 2]],
        ]];

        $data = ['a' => 0, 'b' => 3];

        self::assertTrue(JsonRule::evaluate($rules, $data));
    }

    public function testEvaluateNotOperator(): void
    {
        $rules = ['!' => [[
            '>' => [['var' => 'a'], 5],
        ]]];

        $data = ['a' => 3];

        self::assertTrue(JsonRule::evaluate($rules, $data));
    }

    public function testEvaluateAllComparisonOperators(): void
    {
        $rules = ['and' => [
            ['>' => [['var' => 'a'], 1]],
            ['>=' => [['var' => 'b'], 2]],
            ['<' => [['var' => 'c'], 5]],
            ['<=' => [['var' => 'd'], 4]],
            ['!=' => [['var' => 'e'], 3]],
            ['in' => [['var' => 'f'], [1, 2, 3]]],
        ]];

        $data = [
            'a' => 2,
            'b' => 2,
            'c' => 3,
            'd' => 4,
            'e' => 4,
            'f' => 2,
        ];

        self::assertTrue(JsonRule::evaluate($rules, $data));
    }
}
