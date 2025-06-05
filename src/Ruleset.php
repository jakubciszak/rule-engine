<?php

namespace JakubCiszak\RuleEngine;

use Munus\Control\Either;

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

    public function evaluate(RuleContext $context): Either
    {
        $result = Either::right(Proposition::success());
        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($context);
            if ($result->isLeft()) {
                return $result;
            }
        }

        return $result;
    }
}