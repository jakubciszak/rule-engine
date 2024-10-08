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
        $rule->addElement(Variable::create('a'))
            ->addElement(Variable::create('b'))
            ->equalTo()
            ->addElement(Proposition::create('ruleProposition'))
            ->and()
            ->not()
            ->addElement(Variable::create('amount'))
            ->addElement(Variable::create('minAmount'))
            ->greaterThan()
            ->addElement(Variable::create('amount'))
            ->addElement(Variable::create('maxAmount'))
            ->lessThan()
            ->and()
            ->addElement(Variable::create('bonusPoints'))
            ->addElement(Variable::create('minBonusPoints'))
            ->greaterThanOrEqualTo()
            ->or()
            ->addElement(Variable::create('today', new DateTimeImmutable('2024-06-01')))
            ->addElement(Variable::create('birthday'))
            ->lessThanOrEqualTo()
            ->addElement(Variable::create('state'))
            ->addElement(Variable::create('invalidState'))
            ->notEqualTo()
            ->and();


        $context = new RuleContext();
        $context->addElement(Variable::create('a', 1))
            ->addElement(Variable::create('b', 1))
            ->addElement(Proposition::create('ruleProposition', fn () => true))
            ->addElement(Variable::create('amount', 100))
            ->addElement(Variable::create('minAmount', 50))
            ->addElement(Variable::create('maxAmount', 200))
            ->addElement(Variable::create('bonusPoints', 100))
            ->addElement(Variable::create('minBonusPoints', 100))
            ->addElement(Variable::create('today'))
            ->addElement(Variable::create('birthday', new DateTimeImmutable('2024-06-01')))
            ->addElement(Variable::create('state', 'active'))
            ->addElement(Variable::create('invalidState', 'inactive'));

        $proposition = $rule->evaluate($context);

        self::assertFalse($proposition->getValue());
    }

}
