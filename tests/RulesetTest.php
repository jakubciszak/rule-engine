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

        $result = $ruleset->evaluate(new RuleContext());
        $this->assertTrue($result->isRight());
        $this->assertTrue($result->get()->getValue());
    }

    public function testShouldEvaluationFail(): void
    {
        $ruleset = RuleFixtureBuilder::some()->withFalseProposition()->get();

        $result = $ruleset->evaluate(new RuleContext());
        $this->assertTrue($result->isLeft());
        $this->assertFalse($result->getLeft()->getValue());
    }

}
