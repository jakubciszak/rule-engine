<?php

namespace JakubCiszak\RuleEngine;

class Ruleset
{
    /**
     * @var Rule[]
     */
    private array $rules;

    public function __construct(
        Rule ...$rules
    ) {
        $this->rules = $rules;
    }

    public function addRule(Rule $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    public function evaluate(RuleContext $context): Proposition
    {
        $result = Proposition::create('', false);
        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($context);
            if ($result->getValue() === false) {
                return $result;
            }
        }
        return $result;
    }
}