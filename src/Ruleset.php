<?php

namespace JakubCiszak\RuleEngine;

class Ruleset
{
    /**
     * @var RuleInterface[]
     */
    private array $rules;

    public function __construct(
        RuleInterface ...$rules
    ) {
        $this->rules = $rules;
    }

    public function evaluate(RuleContext $context): Proposition
    {
        $result = Proposition::success();
        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($context);
            if (!$result->getValue()) {
                return $result;
            }
        }

        return $result;
    }
}