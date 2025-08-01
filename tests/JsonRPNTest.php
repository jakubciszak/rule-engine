<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Api\JsonRPN;
use PHPUnit\Framework\TestCase;

final class JsonRPNTest extends TestCase
{
    public function testEvaluateJsonRPNs(): void
    {
        $rulesJson = json_encode([
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'max'],
                        ['type' => 'variable', 'name' => 'amount'],
                        ['type' => 'operator', 'name' => 'GREATER_THAN'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $contextJson = json_encode([
            'a' => 1,
            'b' => 1,
            'amount' => 50,
            'max' => 100,
        ], JSON_THROW_ON_ERROR);

        $resultJson = JsonRPN::evaluate($rulesJson, $contextJson);
        $result = json_decode($resultJson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($result['results'][0]['value']);
        $this->assertFalse($result['results'][1]['value']);
    }

    public function testEvaluateJsonRPNsWithActions(): void
    {
        $rulesJson = json_encode([
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                    'actions' => [
                        'var.count + 1',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'count'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $contextJson = json_encode([
            'a' => 1,
            'b' => 1,
            'count' => 0,
            'expected' => 1,
        ], JSON_THROW_ON_ERROR);

        $resultJson = JsonRPN::evaluate($rulesJson, $contextJson);
        $result = json_decode($resultJson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($result['results'][0]['value']);
        $this->assertTrue($result['results'][1]['value']);
    }

    public function testActionUsingVariableReference(): void
    {
        $rulesJson = json_encode([
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'x'],
                        ['type' => 'variable', 'name' => 'y'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                    'actions' => [
                        'var.count + var.increment',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'count'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $contextJson = json_encode([
            'x' => 1,
            'y' => 1,
            'count' => 1,
            'increment' => 2,
            'expected' => 3,
        ], JSON_THROW_ON_ERROR);

        $resultJson = JsonRPN::evaluate($rulesJson, $contextJson);
        $result = json_decode($resultJson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($result['results'][0]['value']);
        $this->assertTrue($result['results'][1]['value']);
    }

    public function testActionSubtract(): void
    {
        $rulesJson = json_encode([
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                    'actions' => [
                        'var.count - 2',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'count'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $contextJson = json_encode([
            'a' => 1,
            'b' => 1,
            'count' => 10,
            'expected' => 8,
        ], JSON_THROW_ON_ERROR);

        $resultJson = JsonRPN::evaluate($rulesJson, $contextJson);
        $result = json_decode($resultJson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($result['results'][0]['value']);
        $this->assertTrue($result['results'][1]['value']);
    }

    public function testActionConcatenate(): void
    {
        $rulesJson = json_encode([
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'name'],
                        ['type' => 'variable', 'name' => 'before'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                    'actions' => [
                        'var.name . Doe',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'name'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $contextJson = json_encode([
            'name' => 'John',
            'before' => 'John',
            'expected' => 'JohnDoe',
        ], JSON_THROW_ON_ERROR);

        $resultJson = JsonRPN::evaluate($rulesJson, $contextJson);
        $result = json_decode($resultJson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($result['results'][0]['value']);
        $this->assertTrue($result['results'][1]['value']);
    }

    public function testActionSet(): void
    {
        $rulesJson = json_encode([
            'rules' => [
                [
                    'name' => 'rule1',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'a'],
                        ['type' => 'variable', 'name' => 'b'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                    'actions' => [
                        'var.status = done',
                    ],
                ],
                [
                    'name' => 'rule2',
                    'elements' => [
                        ['type' => 'variable', 'name' => 'status'],
                        ['type' => 'variable', 'name' => 'expected'],
                        ['type' => 'operator', 'name' => 'EQUAL_TO'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $contextJson = json_encode([
            'a' => 1,
            'b' => 1,
            'status' => 'pending',
            'expected' => 'done',
        ], JSON_THROW_ON_ERROR);

        $resultJson = JsonRPN::evaluate($rulesJson, $contextJson);
        $result = json_decode($resultJson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($result['results'][0]['value']);
        $this->assertTrue($result['results'][1]['value']);
    }
}
