<?php

namespace JakubCiszak\RuleEngine\Tests;

use DateTimeImmutable;
use JakubCiszak\RuleEngine\Proposition;
use JakubCiszak\RuleEngine\Rule;
use JakubCiszak\RuleEngine\RuleContext;
use JakubCiszak\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;

class RuleTest extends TestCase
{
    public function testEvaluation(): void
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

        $proposition = $rule->evaluate($context);

        self::assertFalse($proposition->getValue());
    }

}
