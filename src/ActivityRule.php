<?php

namespace JakubCiszak\RuleEngine;

use Closure;

/**
 * @template T of callable(RuleContext): mixed
 */
final class ActivityRule
{
    /** @var T */
    private readonly Closure $activity;

    /**
     * @param Rule $rule
     * @param T $activity
     */
    public function __construct(
        private readonly Rule $rule,
        callable $activity
    ) {
        $this->activity = Closure::fromCallable($activity);
    }

    public function evaluate(RuleContext $context): Proposition
    {
        $result = $this->rule->evaluate($context);
        ($this->activity)($context);
        return $result;
    }
}
