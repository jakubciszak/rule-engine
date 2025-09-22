<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

use Closure;

final readonly class ActivityRule implements RuleInterface
{
    public string $name;

    /** @var Closure(RuleContext):mixed */
    private Closure $activity;

    /**
     * @param Closure(RuleContext):mixed $activity
     */
    public function __construct(
        private RuleInterface $rule,
        Closure $activity,
    ) {
        $this->activity = $activity;
        $this->name = $rule->name;
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

    /**
     * @param null|bool|Closure(RuleContext):bool $value
     */
    public function proposition(string $name, null|bool|Closure $value = true): self
    {
        $this->rule->proposition($name, $value);

        return $this;
    }

    public function evaluate(RuleContext $context): Proposition
    {
        $result = $this->rule->evaluate($context);
        if ($result->getValue()) {
            ($this->activity)($context);
        }

        return $result;
    }
}

