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
}
