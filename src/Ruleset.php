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
        $overallSuccess = true;
        $lastResult = Proposition::success();
        
        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($context);
            $lastResult = $result;
            
            if (!$result->getValue()) {
                $overallSuccess = false;
            }
        }

        return $overallSuccess ? $lastResult : Proposition::create('ruleset_failure', false);
    }
}