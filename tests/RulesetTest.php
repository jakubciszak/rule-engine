<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\ActivityRule;
use JakubCiszak\RuleEngine\Proposition;
use JakubCiszak\RuleEngine\Rule;
use JakubCiszak\RuleEngine\RuleContext;
use JakubCiszak\RuleEngine\Ruleset;
use JakubCiszak\RuleEngine\Tests\Fixtures\RuleFixtureBuilder;
use JakubCiszak\RuleEngine\Variable;
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

    public function testProcessAllRulesEvenWhenSomeFail(): void
    {
        // Create multiple activity rules that modify context
        $firstRuleProcessed = false;
        $secondRuleProcessed = false;
        $thirdRuleProcessed = false;

        // First rule: succeeds and sets a flag
        $firstRule = new Rule('first');
        $firstRule->variable('a')->variable('b')->equalTo();
        $firstActivityRule = new ActivityRule($firstRule, function (RuleContext $context) use (&$firstRuleProcessed) {
            $firstRuleProcessed = true;
            $context->variable('first_executed', true);
        });

        // Second rule: fails but should still be processed
        $secondRule = new Rule('second');
        $secondRule->variable('x')->variable('y')->equalTo();
        $secondActivityRule = new ActivityRule($secondRule, function (RuleContext $context) use (&$secondRuleProcessed) {
            $secondRuleProcessed = true;
            $context->variable('second_executed', true);
        });

        // Third rule: succeeds and should be processed even though second failed
        $thirdRule = new Rule('third');
        $thirdRule->variable('p')->variable('q')->equalTo();
        $thirdActivityRule = new ActivityRule($thirdRule, function (RuleContext $context) use (&$thirdRuleProcessed) {
            $thirdRuleProcessed = true;
            $context->variable('third_executed', true);
        });

        $ruleset = new Ruleset($firstActivityRule, $secondActivityRule, $thirdActivityRule);

        $context = new RuleContext();
        $context->variable('a', 1)
            ->variable('b', 1)  // first rule will succeed
            ->variable('x', 1)
            ->variable('y', 2)  // second rule will fail
            ->variable('p', 5)
            ->variable('q', 5); // third rule will succeed

        $result = $ruleset->evaluate($context);

        // Overall result should be false because second rule failed
        $this->assertInstanceOf(Proposition::class, $result);
        $this->assertFalse($result->getValue());

        // But all rules should have been processed
        $this->assertTrue($firstRuleProcessed, 'First rule should have been processed');
        $this->assertFalse($secondRuleProcessed, 'Second rule should have been processed but activity not executed due to failure');
        $this->assertTrue($thirdRuleProcessed, 'Third rule should have been processed');

        // First and third rule activities should have executed and modified context
        $firstExecuted = $context->findElement(Variable::create('first_executed'));
        $this->assertInstanceOf(Variable::class, $firstExecuted);
        $this->assertTrue($firstExecuted->getValue());

        $secondExecuted = $context->findElement(Variable::create('second_executed'));
        $this->assertNull($secondExecuted); // Activity didn't execute because rule failed

        $thirdExecuted = $context->findElement(Variable::create('third_executed'));
        $this->assertInstanceOf(Variable::class, $thirdExecuted);
        $this->assertTrue($thirdExecuted->getValue());
    }

    public function testProcessAllRulesWhenAllSucceed(): void
    {
        // Create multiple activity rules that all succeed
        $firstRuleProcessed = false;
        $secondRuleProcessed = false;
        $thirdRuleProcessed = false;

        // All rules succeed and set flags
        $firstRule = new Rule('first');
        $firstRule->variable('a')->variable('b')->equalTo();
        $firstActivityRule = new ActivityRule($firstRule, function (RuleContext $context) use (&$firstRuleProcessed) {
            $firstRuleProcessed = true;
            $context->variable('first_executed', true);
        });

        $secondRule = new Rule('second');
        $secondRule->variable('x')->variable('y')->equalTo();
        $secondActivityRule = new ActivityRule($secondRule, function (RuleContext $context) use (&$secondRuleProcessed) {
            $secondRuleProcessed = true;
            $context->variable('second_executed', true);
        });

        $thirdRule = new Rule('third');
        $thirdRule->variable('p')->variable('q')->equalTo();
        $thirdActivityRule = new ActivityRule($thirdRule, function (RuleContext $context) use (&$thirdRuleProcessed) {
            $thirdRuleProcessed = true;
            $context->variable('third_executed', true);
        });

        $ruleset = new Ruleset($firstActivityRule, $secondActivityRule, $thirdActivityRule);

        $context = new RuleContext();
        $context->variable('a', 1)
            ->variable('b', 1)  // first rule will succeed
            ->variable('x', 5)
            ->variable('y', 5)  // second rule will succeed
            ->variable('p', 10)
            ->variable('q', 10); // third rule will succeed

        $result = $ruleset->evaluate($context);

        // Overall result should be true because all rules succeeded
        $this->assertInstanceOf(Proposition::class, $result);
        $this->assertTrue($result->getValue());

        // All rules should have been processed and activities executed
        $this->assertTrue($firstRuleProcessed, 'First rule should have been processed');
        $this->assertTrue($secondRuleProcessed, 'Second rule should have been processed');
        $this->assertTrue($thirdRuleProcessed, 'Third rule should have been processed');

        // All activities should have executed and modified context
        $firstExecuted = $context->findElement(Variable::create('first_executed'));
        $this->assertInstanceOf(Variable::class, $firstExecuted);
        $this->assertTrue($firstExecuted->getValue());

        $secondExecuted = $context->findElement(Variable::create('second_executed'));
        $this->assertInstanceOf(Variable::class, $secondExecuted);
        $this->assertTrue($secondExecuted->getValue());

        $thirdExecuted = $context->findElement(Variable::create('third_executed'));
        $this->assertInstanceOf(Variable::class, $thirdExecuted);
        $this->assertTrue($thirdExecuted->getValue());
    }

}
