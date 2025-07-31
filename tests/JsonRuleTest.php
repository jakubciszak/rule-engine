<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Api\JsonRule;
use PHPUnit\Framework\TestCase;

final class JsonRuleTest extends TestCase
{
    public function testApplyArrayRules(): void
    {
        $rules = ['and' => [
            ['<' => [['var' => 'temp'], 110]],
            ['==' => [['var' => 'pie.filling'], 'apple']],
        ]];

        $data = ['temp' => 100, 'pie' => ['filling' => 'apple']];

        self::assertTrue(JsonRule::apply($rules, $data));
    }

    public function testApplyJsonStrings(): void
    {
        $rules = ['==' => [['var' => 'a'], 1]];
        $data = ['a' => 2];

        $rulesJson = json_encode($rules, JSON_THROW_ON_ERROR);
        $dataJson = json_encode($data, JSON_THROW_ON_ERROR);

        self::assertFalse(JsonRule::apply($rulesJson, $dataJson));
    }
}
