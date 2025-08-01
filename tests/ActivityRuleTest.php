<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\ActivityRule;
use JakubCiszak\RuleEngine\Rule;
use JakubCiszak\RuleEngine\RuleContext;
use JakubCiszak\RuleEngine\Variable;
use JakubCiszak\RuleEngine\Proposition;
use PHPUnit\Framework\TestCase;

class ActivityRuleTest extends TestCase
{
    public function testEvaluateDecoratesRule(): void
    {
        $rule = new Rule('someRule');
        $rule->variable('a')
            ->variable('b')
            ->equalTo();

        $called = false;
        $activity = function (RuleContext $context) use (&$called) {
            $called = true;
            $context->variable('activity', true);
        };

        $activityRule = new ActivityRule($rule, $activity);

        $context = new RuleContext();
        $context->variable('a', 1)
            ->variable('b', 1);

        $result = $activityRule->evaluate($context);

        $this->assertInstanceOf(Proposition::class, $result);
        $this->assertTrue($result->getValue());
        $this->assertTrue($called);
        $variable = $context->findElement(Variable::create('activity'));
        $this->assertInstanceOf(Variable::class, $variable);
        $this->assertTrue($variable->getValue());
    }
}
