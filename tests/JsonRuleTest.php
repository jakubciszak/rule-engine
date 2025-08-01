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

    public function testEvaluateRulesetArray(): void
    {
        $ruleset = [
            'rule1' => ['and' => [
                ['<' => [['var' => 'temp'], 110]],
                ['==' => [['var' => 'pie.filling'], 'apple']],
            ]],
            'rule2' => ['and' => [
                ['<' => [['var' => 'temp'], 110]],
                ['==' => [['var' => 'pie.filling'], 'apple']],
            ]],
        ];

        $data = ['temp' => 100, 'pie' => ['filling' => 'apple']];

        self::assertTrue(JsonRule::evaluate($ruleset, $data));
    }

    public function testEvaluateRulesetJson(): void
    {
        $ruleset = [
            'rule1' => ['==' => [['var' => 'a'], 1]],
            'rule2' => ['>' => [['var' => 'b'], 2]],
        ];
        $data = ['a' => 1, 'b' => 3];

        $rulesetJson = json_encode($ruleset, JSON_THROW_ON_ERROR);
        $dataJson = json_encode($data, JSON_THROW_ON_ERROR);

        self::assertTrue(JsonRule::evaluate($rulesetJson, $dataJson));
    }

    public function testEvaluateRulesetWithActions(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'a'], 1],
                'actions' => ['var.count + 1'],
            ],
            'rule2' => [
                '==' => [['var' => 'count'], 1],
            ],
        ];

        $data = ['a' => 1, 'count' => 0];

        self::assertTrue(JsonRule::evaluate($ruleset, $data));
    }

    public function testActionUsingVariableReference(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'x'], 1],
                'actions' => ['var.count + var.increment'],
            ],
            'rule2' => [
                '==' => [['var' => 'count'], 3],
            ],
        ];

        $data = ['x' => 1, 'count' => 1, 'increment' => 2];

        self::assertTrue(JsonRule::evaluate($ruleset, $data));
    }

    public function testActionSubtract(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'a'], 1],
                'actions' => ['var.count - 2'],
            ],
            'rule2' => [
                '==' => [['var' => 'count'], 8],
            ],
        ];

        $data = ['a' => 1, 'count' => 10];

        self::assertTrue(JsonRule::evaluate($ruleset, $data));
    }

    public function testActionConcatenate(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'name'], 'John'],
                'actions' => ['var.name . Doe'],
            ],
            'rule2' => [
                '==' => [['var' => 'name'], 'JohnDoe'],
            ],
        ];

        $data = ['name' => 'John'];

        self::assertTrue(JsonRule::evaluate($ruleset, $data));
    }

    public function testActionSet(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'a'], 1],
                'actions' => ['var.status = done'],
            ],
            'rule2' => [
                '==' => [['var' => 'status'], 'done'],
            ],
        ];

        $data = ['a' => 1, 'status' => 'pending'];

        self::assertTrue(JsonRule::evaluate($ruleset, $data));
    }
}
