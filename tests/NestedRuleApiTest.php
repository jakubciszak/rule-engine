<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Api\NestedRuleApi;
use PHPUnit\Framework\TestCase;

final class NestedRuleApiTest extends TestCase
{
    public function testEvaluateArrayRules(): void
    {
        $rules = ['and' => [
            ['<' => [['var' => 'temp'], 110]],
            ['==' => [['var' => 'pie.filling'], 'apple']],
        ]];

        $data = ['temp' => 100, 'pie' => ['filling' => 'apple']];

        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
    }

    public function testEvaluateJsonStrings(): void
    {
        $rules = ['==' => [['var' => 'a'], 1]];
        $data = ['a' => 2];

        $rulesJson = json_encode($rules, JSON_THROW_ON_ERROR);
        $dataJson = json_encode($data, JSON_THROW_ON_ERROR);

        $decodedRules = json_decode($rulesJson, true, 512, JSON_THROW_ON_ERROR);
        $decodedData = json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse(
            NestedRuleApi::evaluate(
                $decodedRules,
                $decodedData
            )->result
        );
    }

    public function testEvaluateOrOperator(): void
    {
        $rules = ['or' => [
            ['==' => [['var' => 'a'], 1]],
            ['>' => [['var' => 'b'], 2]],
        ]];

        $data = ['a' => 0, 'b' => 3];

        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
    }

    public function testEvaluateNotOperator(): void
    {
        $rules = ['!' => [[
            '>' => [['var' => 'a'], 5],
        ]]];

        $data = ['a' => 3];

        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
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

        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
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

        self::assertTrue(NestedRuleApi::evaluate($ruleset, $data)->result);
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

        $decodedRuleset = json_decode($rulesetJson, true, 512, JSON_THROW_ON_ERROR);
        $decodedData = json_decode($dataJson, true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue(
            NestedRuleApi::evaluate(
                $decodedRuleset,
                $decodedData
            )->result
        );
    }

    public function testEvaluateRulesetWithActions(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'a'], 1],
                'actions' => ['.count + 1'],
            ],
            'rule2' => [
                '==' => [['var' => 'count'], 1],
            ],
        ];

        $data = ['a' => 1, 'count' => 0];

        $result = NestedRuleApi::evaluate($ruleset, $data);
        self::assertTrue($result->result);
        self::assertSame(0, $data['count']);
        self::assertSame(1, $result->context['count']);
    }

    public function testActionUsingVariableReference(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'x'], 1],
                'actions' => ['.count + .increment'],
            ],
            'rule2' => [
                '==' => [['var' => 'count'], 3],
            ],
        ];

        $data = ['x' => 1, 'count' => 1, 'increment' => 2];

        $result = NestedRuleApi::evaluate($ruleset, $data);
        self::assertTrue($result->result);
        self::assertSame(1, $data['count']);
        self::assertSame(3, $result->context['count']);
    }

    public function testActionSubtract(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'a'], 1],
                'actions' => ['.count - 2'],
            ],
            'rule2' => [
                '==' => [['var' => 'count'], 8],
            ],
        ];

        $data = ['a' => 1, 'count' => 10];

        $result = NestedRuleApi::evaluate($ruleset, $data);
        self::assertTrue($result->result);
        self::assertSame(10, $data['count']);
        self::assertSame(8, $result->context['count']);
    }

    public function testActionConcatenate(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'name'], 'John'],
                'actions' => ['.name . Doe'],
            ],
            'rule2' => [
                '==' => [['var' => 'name'], 'JohnDoe'],
            ],
        ];

        $data = ['name' => 'John'];

        $result = NestedRuleApi::evaluate($ruleset, $data);
        self::assertTrue($result->result);
        self::assertSame('John', $data['name']);
        self::assertSame('JohnDoe', $result->context['name']);
    }

    public function testActionSet(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'a'], 1],
                'actions' => ['.status = done'],
            ],
            'rule2' => [
                '==' => [['var' => 'status'], 'done'],
            ],
        ];

        $data = ['a' => 1, 'status' => 'pending'];

        $result = NestedRuleApi::evaluate($ruleset, $data);
        self::assertTrue($result->result);
        self::assertSame('pending', $data['status']);
        self::assertSame('done', $result->context['status']);
    }

    public function testCallableProposition(): void
    {
        $rules = ['and' => [
            ['var' => 'check'],
        ]];

        $data = ['check' => fn () => true];

        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
    }

    public function testActionInitializesMissingVariable(): void
    {
        $ruleset = [
            'rule1' => [
                '==' => [['var' => 'a'], 1],
                'actions' => ['.generated + 1'],
            ],
            'rule2' => [
                '==' => [['var' => 'generated'], 1],
            ],
        ];

        $data = ['a' => 1];

        $result = NestedRuleApi::evaluate($ruleset, $data);
        self::assertTrue($result->result);
        self::assertArrayNotHasKey('generated', $data);
        self::assertSame(1, $result->context['generated']);
    }

    public function testWildcardExpansion(): void
    {
        // Test Example 1 from the issue - checking all streets are not empty
        $rules = [
            'and' => [
                ['!=' => [['var' => 'addresses.*.street'], '']]
            ]
        ];

        $data = [
            'addresses' => [
                ['street' => 'Długa 1', 'city' => 'Warsaw', 'zip' => '00-001'],
                ['street' => '',        'city' => 'Kraków', 'zip' => '31-001'],
                ['street' => 'Ogrodowa 7', 'city' => 'Gdańsk', 'zip' => '80-001']
            ]
        ];

        // This should expand to check addresses.0.street, addresses.1.street, addresses.2.street
        // The rule should fail because addresses.1.street is empty
        $result = NestedRuleApi::evaluate($rules, $data);
        self::assertFalse($result->result);
        
        // Verify the context was flattened and contains the expanded keys
        self::assertArrayNotHasKey('addresses.0.street', $data);
        self::assertArrayHasKey('addresses.0.street', $result->context);
        self::assertArrayHasKey('addresses.1.street', $result->context);
        self::assertArrayHasKey('addresses.2.street', $result->context);
        self::assertSame('Długa 1', $result->context['addresses.0.street']);
        self::assertSame('', $result->context['addresses.1.street']);
        self::assertSame('Ogrodowa 7', $result->context['addresses.2.street']);
    }

    public function testWildcardWithOperators(): void
    {
        // Test Example 2 from the issue - using actual operators
        $rules = [
            'and' => [
                ['==' => [['var' => 'addresses.*.zip'], '^[0-9]{2}-[0-9]{3}$']], // This would need regex operator
                ['in' => [['var' => 'addresses.*.city'], ['Warsaw', 'Gdańsk']]]
            ]
        ];

        $data = [
            'addresses' => [
                ['street' => 'Długa 1', 'city' => 'Warsaw', 'zip' => '00-001'],
                ['street' => 'Rynek 2', 'city' => 'Kraków', 'zip' => '31-001']
            ]
        ];

        // This should expand to check all addresses
        // Should fail because Kraków is not in ['Warsaw', 'Gdańsk']
        self::assertFalse(NestedRuleApi::evaluate($rules, $data)->result);
    }

    public function testWildcardSuccess(): void
    {
        // Test case where wildcard rules should pass
        $rules = [
            'and' => [
                ['!=' => [['var' => 'addresses.*.street'], '']] // All streets should be non-empty
            ]
        ];

        $data = [
            'addresses' => [
                ['street' => 'Długa 1', 'city' => 'Warsaw'],
                ['street' => 'Ogrodowa 7', 'city' => 'Gdańsk']
            ]
        ];

        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
    }

    public function testWildcardWithEmptyArray(): void
    {
        // Test wildcard with empty array
        $rules = [
            'and' => [
                ['!=' => [['var' => 'items.*.name'], '']]
            ]
        ];

        $data = [
            'items' => []
        ];

        // Should return true when array is empty (vacuous truth)
        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
    }

    public function testWildcardWithNonArrayValue(): void
    {
        // Test wildcard when the referenced key is not an array
        $rules = [
            'and' => [
                ['!=' => [['var' => 'value.*.name'], '']]
            ]
        ];

        $data = [
            'value' => 'not_an_array'
        ];

        // Should return true when no wildcard matches found (vacuous truth)
        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
    }

    public function testMultipleWildcards(): void
    {
        // Test multiple wildcard expressions
        $rules = [
            'and' => [
                ['!=' => [['var' => 'users.*.name'], '']],
                ['>' => [['var' => 'users.*.age'], 0]]
            ]
        ];

        $data = [
            'users' => [
                ['name' => 'John', 'age' => 25],
                ['name' => 'Jane', 'age' => 30]
            ]
        ];

        self::assertTrue(NestedRuleApi::evaluate($rules, $data)->result);
    }

    public function testNestedWildcards(): void
    {
        // Test nested arrays with wildcards
        $rules = [
            'and' => [
                ['!=' => [['var' => 'departments.*.employees.*.name'], '']]
            ]
        ];

        $data = [
            'departments' => [
                [
                    'name' => 'Engineering',
                    'employees' => [
                        ['name' => 'Alice', 'role' => 'Developer'],
                        ['name' => 'Bob', 'role' => 'Architect']
                    ]
                ],
                [
                    'name' => 'Marketing', 
                    'employees' => [
                        ['name' => 'Charlie', 'role' => 'Manager']
                    ]
                ]
            ]
        ];

        $result = NestedRuleApi::evaluate($rules, $data);
        self::assertTrue($result->result);

        // Verify flattened structure
        self::assertArrayNotHasKey('departments.0.employees.0.name', $data);
        self::assertArrayHasKey('departments.0.employees.0.name', $result->context);
        self::assertArrayHasKey('departments.0.employees.1.name', $result->context);
        self::assertArrayHasKey('departments.1.employees.0.name', $result->context);
        self::assertSame('Alice', $result->context['departments.0.employees.0.name']);
        self::assertSame('Bob', $result->context['departments.0.employees.1.name']);
        self::assertSame('Charlie', $result->context['departments.1.employees.0.name']);
    }

    public function testWildcardExampleFromIssue(): void
    {
        // Test exact example from issue description (Example 2)
        $rules = [
            'and' => [
                ['in' => [['var' => 'addresses.*.city'], ['Warsaw', 'Gdańsk']]]
            ]
        ];

        $data = [
            'addresses' => [
                ['street' => 'Długa 1', 'city' => 'Warsaw', 'zip' => '00-001'],
                ['street' => 'Rynek 2', 'city' => 'Kraków', 'zip' => '31-001']
            ]
        ];

        // Should fail because Kraków is not in ['Warsaw', 'Gdańsk']
        self::assertFalse(NestedRuleApi::evaluate($rules, $data)->result);
        
        // Test success case
        $data2 = [
            'addresses' => [
                ['street' => 'Długa 1', 'city' => 'Warsaw', 'zip' => '00-001'],
                ['street' => 'Rynek 2', 'city' => 'Gdańsk', 'zip' => '31-001']
            ]
        ];

        // Should pass because both Warsaw and Gdańsk are in the allowed list
        self::assertTrue(NestedRuleApi::evaluate($rules, $data2)->result);
    }
}
