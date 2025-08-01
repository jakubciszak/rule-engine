<?php

namespace JakubCiszak\RuleEngine;

use Closure;

/**
 * @template T of callable(RuleContext): mixed
 */
final class ActivityRule implements RuleInterface
{
    /** @var T */
    private readonly Closure $activity;

    /**
     * @param RuleInterface $rule
     * @param T $activity
     */
    public function __construct(
        private readonly RuleInterface $rule,
        callable $activity
    ) {
        $this->activity = Closure::fromCallable($activity);
    }

    public function and(): self
    {
        $this->rule->and();

        return $this;
    }

    public function or(): self
    {
        $this->rule->or();

        return $this;
    }

    public function not(): self
    {
        $this->rule->not();

        return $this;
    }

    public function equalTo(): self
    {
        $this->rule->equalTo();

        return $this;
    }

    public function notEqualTo(): self
    {
        $this->rule->notEqualTo();

        return $this;
    }

    public function greaterThan(): self
    {
        $this->rule->greaterThan();

        return $this;
    }

    public function lessThan(): self
    {
        $this->rule->lessThan();

        return $this;
    }

    public function greaterThanOrEqualTo(): self
    {
        $this->rule->greaterThanOrEqualTo();

        return $this;
    }

    public function lessThanOrEqualTo(): self
    {
        $this->rule->lessThanOrEqualTo();

        return $this;
    }

    public function in(): self
    {
        $this->rule->in();

        return $this;
    }

    public function addElement(RuleElement $element): self
    {
        $this->rule->addElement($element);

        return $this;
    }

    public function variable(string $name, mixed $value = null): self
    {
        $this->rule->variable($name, $value);

        return $this;
    }

    public function proposition(string $name, null|Closure|bool $closure = true): self
    {
        $this->rule->proposition($name, $closure);

        return $this;
    }

    public function evaluate(RuleContext $context): Proposition
    {
        $result = $this->rule->evaluate($context);
        ($this->activity)($context);

        return $result;
    }
}
