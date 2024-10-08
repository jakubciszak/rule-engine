<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Proposition;
use JakubCiszak\RuleEngine\RuleContext;
use JakubCiszak\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;


class RuleContextTest extends TestCase
{
    public function testAddingElements(): void
    {
        $context = new RuleContext();
        $context->addElement(Variable::create('a', 'value'));
        $context->addElement(Proposition::create('someRuleElement'));

        $variable  = Variable::create('a');
        $element = $context->findElement($variable);

        self::assertInstanceOf(Variable::class, $element);
        self::assertEquals('value', $element->getValue());

        $proposition = Proposition::create('someRuleElement');
        $element = $context->findElement($proposition);

        self::assertInstanceOf(Proposition::class, $element);
        self::assertTrue($element->getValue());
    }

    public function testAppend(): void
    {
        $context = new RuleContext();
        $context->addElement(Variable::create('a', 'value'));
        $context->addElement(Proposition::create('someRuleElement'));

        $context2 = new RuleContext();
        $context2->addElement(Variable::create('b', 'value2'));
        $context2->addElement(Proposition::create('someRuleElement2', false));

        $newContext = $context->append($context2);

        $variableA  = Variable::create('a');
        $element = $newContext->findElement($variableA);
        self::assertInstanceOf(Variable::class, $element);
        self::assertEquals('value', $element->getValue());

        $variableB  = Variable::create('b');
        $element = $newContext->findElement($variableB);
        self::assertInstanceOf(Variable::class, $element);
        self::assertEquals('value2', $element->getValue());

        $proposition1 = Proposition::create('someRuleElement');
        $element = $newContext->findElement($proposition1);
        self::assertInstanceOf(Proposition::class, $element);
        self::assertTrue($element->getValue());

        $proposition2 = Proposition::create('someRuleElement2');
        $element = $newContext->findElement($proposition2);
        self::assertInstanceOf(Proposition::class, $element);
        self::assertFalse($element->getValue());
    }
}
