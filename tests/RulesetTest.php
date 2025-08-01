<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Proposition;
use JakubCiszak\RuleEngine\RuleContext;
use JakubCiszak\RuleEngine\Tests\Fixtures\RuleFixtureBuilder;
use PHPUnit\Framework\TestCase;

class RulesetTest extends TestCase
{
    public function testEvaluateRulesInSet(): void
    {
        $ruleset = RuleFixtureBuilder::some()->withTrueProposition()->get();

        $result = $ruleset->evaluate(new RuleContext());
        $this->assertInstanceOf(Proposition::class, $result);
        $this->assertTrue($result->getValue());
    }

    public function testShouldEvaluationFail(): void
    {
        $ruleset = RuleFixtureBuilder::some()->withFalseProposition()->get();

        $result = $ruleset->evaluate(new RuleContext());
        $this->assertInstanceOf(Proposition::class, $result);
        $this->assertFalse($result->getValue());
    }

}
