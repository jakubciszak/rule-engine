<?php

namespace JakubCiszak\RuleEngine\Tests\Fixtures;

use JakubCiszak\RuleEngine\Rule;

final readonly class RuleFixtureBuilder
{
    public function __construct(private Rule $rule)
    {
    }

    public static function some(): self
    {
        return new self(self::createRule());
    }

    private static function createRule(): Rule
    {
        return new Rule(uniqid('rule_', true));
    }

    public function withFalseProposition(): self
    {
        $this->rule->addElement(RuleElementFixture::createFalseProposition());
        return $this;
    }

    public function withTrueProposition(): self
    {
        $this->rule->addElement(RuleElementFixture::createTrueProposition());
        return $this;
    }

    public function get(): Rule
    {
        return $this->rule;
    }

}