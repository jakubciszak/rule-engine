<?php

namespace JakubCiszak\RuleEngine\Tests;

use DateTimeImmutable;
use JakubCiszak\RuleEngine\Rule;
use JakubCiszak\RuleEngine\RuleContext;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    public function testEvaluationFail(): void
    {
        $rule = new Rule('someRule');
        $rule->variable('a')
            ->variable('b')
            ->equalTo()
            ->proposition('ruleProposition')
            ->and()
            ->not()
            ->variable('amount')
            ->variable('minAmount')
            ->greaterThan()
            ->variable('amount')
            ->variable('maxAmount')
            ->lessThan()
            ->and()
            ->variable('bonusPoints')
            ->variable('minBonusPoints')
            ->greaterThanOrEqualTo()
            ->or()
            ->variable('today', new DateTimeImmutable('2024-06-01'))
            ->variable('birthday')
            ->lessThanOrEqualTo()
            ->variable('state')
            ->variable('invalidState')
            ->notEqualTo()
            ->and();


        $context = new RuleContext();
        $context->variable('a', 1)
            ->variable('b', 1)
            ->proposition('ruleProposition', fn () => true)
            ->variable('amount', 100)
            ->variable('minAmount', 50)
            ->variable('maxAmount', 200)
            ->variable('bonusPoints', 100)
            ->variable('minBonusPoints', 100)
            ->variable('today')
            ->variable('birthday', new DateTimeImmutable('2024-06-01'))
            ->variable('state', 'active')
            ->variable('invalidState', 'inactive');

        $result = $rule->evaluate($context);

        self::assertTrue($result->isLeft());
        self::assertFalse($result->getLeft()->getValue());
    }

    public function testShouldEvaluationSuccess(): void
    {
        $rule = new Rule('someRule');
        $rule->variable('a')
            ->variable('b')
            ->equalTo()
            ->variable('maxAmount')
            ->variable('amount')
            ->lessThanOrEqualTo()
            ->and()
            ->proposition('ruleProposition')
            ->or()
            ->variable('empty')
            ->variable('anotherEmpty')
            ->equalTo()
            ->and()
            ->variable('allowed_id', [1, 2, 3])
            ->variable('id')
            ->in()
            ->and();


        $context = new RuleContext();
        $context->variable('a', 1)
            ->variable('b', 1)
            ->variable('maxAmount', 200)
            ->variable('amount', 100)
            ->variable('id', 1)
            ->proposition('ruleProposition', fn () => false);

        $result = $rule->evaluate($context);

        self::assertTrue($result->isRight());
        self::assertTrue($result->get()->getValue());
    }

    public function testEvaluateBetweenOperator(): void
    {
        $rule = new Rule('betweenRule');
        $rule->variable('range', [1, 10])
            ->variable('value')
            ->between();

        $context = new RuleContext();
        $context->variable('value', 5);

        $result = $rule->evaluate($context);

        self::assertTrue($result->isRight());
        self::assertTrue($result->get()->getValue());

        $contextInvalid = new RuleContext();
        $contextInvalid->variable('value', 15);

        $resultInvalid = $rule->evaluate($contextInvalid);

        self::assertTrue($resultInvalid->isLeft());
        self::assertFalse($resultInvalid->getLeft()->getValue());
    }
}
