<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Api\FlatRuleAPI;
use PHPUnit\Framework\TestCase;

final class FlatRuleAPITest extends TestCase
{
    public function testEvaluateFlatRuleAPIs(): void
    {
        $rules = [
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'max'],
                        ['type' => 'variable', 'name' => 'amount'],
                        ['type' => 'operator', 'name' => '>'],
                    ],
                ],
            ],
        ];

        $context = [
            'a' => 1,
            'b' => 1,
            'amount' => 50,
            'max' => 100,
        ];

        $result = FlatRuleAPI::evaluate($rules, $context);

        $this->assertFalse($result);
    }

    public function testEvaluateFlatRuleAPIsWithActions(): void
    {
        $rules = [
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                    'actions' => [
                        '.count + 1',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'count'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                ],
            ],
        ];

        $context = [
            'a' => 1,
            'b' => 1,
            'count' => 0,
            'expected' => 1,
        ];

        $result = FlatRuleAPI::evaluate($rules, $context);

        $this->assertTrue($result);
        $this->assertSame(1, $context['count']);
    }

    public function testActionUsingVariableReference(): void
    {
        $rules = [
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'x'],
                        ['type' => 'variable', 'name' => 'y'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                    'actions' => [
                        '.count + .increment',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'count'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                ],
            ],
        ];

        $context = [
            'x' => 1,
            'y' => 1,
            'count' => 1,
            'increment' => 2,
            'expected' => 3,
        ];

        $result = FlatRuleAPI::evaluate($rules, $context);

        $this->assertTrue($result);
        $this->assertSame(3, $context['count']);
    }

    public function testActionSubtract(): void
    {
        $rules = [
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                    'actions' => [
                        '.count - 2',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'count'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                ],
            ],
        ];

        $context = [
            'a' => 1,
            'b' => 1,
            'count' => 10,
            'expected' => 8,
        ];

        $result = FlatRuleAPI::evaluate($rules, $context);

        $this->assertTrue($result);
        $this->assertSame(8, $context['count']);
    }

    public function testActionConcatenate(): void
    {
        $rules = [
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'name'],
                        ['type' => 'variable', 'name' => 'before'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                    'actions' => [
                        '.name . Doe',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'name'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                ],
            ],
        ];

        $context = [
            'name' => 'John',
            'before' => 'John',
            'expected' => 'JohnDoe',
        ];

        $result = FlatRuleAPI::evaluate($rules, $context);

        //$this->assertTrue($result);
        $this->assertSame('JohnDoe', $context['name']);
    }

    public function testActionSet(): void
    {
        $rules = [
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                    'actions' => [
                        '.status = done',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'status'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                ],
            ],
        ];

        $context = [
            'a' => 1,
            'b' => 1,
            'status' => 'pending',
            'expected' => 'done',
        ];

        $result = FlatRuleAPI::evaluate($rules, $context);

        $this->assertTrue($result);
        $this->assertSame('done', $context['status']);
    }

    public function testCallablePropositionInContext(): void
    {
        $rules = [
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'proposition', 'name' => 'flag'],
                    ],
                ],
            ],
        ];

        $context = ['flag' => fn () => true];

        $result = FlatRuleAPI::evaluate($rules, $context);

        $this->assertTrue($result);
    }

    public function testActionInitializesMissingVariable(): void
    {
        $rules = [
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                    'actions' => ['.generated + 1'],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'generated'],
                        ['type' => 'variable', 'name' => 'expected', 'value' => 1],
                        ['type' => 'operator', 'name' => '=='],
                    ],
                ],
            ],
        ];

        $context = [
            'a' => 1,
            'b' => 1,
        ];

        $result = FlatRuleAPI::evaluate($rules, $context);

        $this->assertTrue($result);
        $this->assertSame(1, $context['generated']);
    }
}
