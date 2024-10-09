<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\RuleContext;
use JakubCiszak\RuleEngine\Tests\Fixtures\RuleFixtureBuilder;
use PHPUnit\Framework\TestCase;

class RulesetTest extends TestCase
{
    public function testEvaluateRulesInSet(): void
    {
        $ruleset = RuleFixtureBuilder::some()->withTrueProposition()->get();

        $this->assertTrue($ruleset->evaluate(new RuleContext())->getValue());
    }

    public function testShouldEvaluationFail(): void
    {
        $ruleset = RuleFixtureBuilder::some()->withFalseProposition()->get();

        $this->assertFalse($ruleset->evaluate(new RuleContext())->getValue());
    }

}
