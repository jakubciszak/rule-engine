<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Rule;
use JakubCiszak\RuleEngine\RuleContext;
use JakubCiszak\RuleEngine\Ruleset;
use PHPUnit\Framework\TestCase;

class RulesetTest extends TestCase
{
    public function testEvaluateRulesInSet(): void
    {
        $ruleset = new Ruleset(
            new Rule('test'),
            new Rule('test')
        );

        $this->assertTrue($ruleset->evaluate(new RuleContext())->getValue());
    }

}
