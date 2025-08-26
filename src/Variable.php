<?php

namespace JakubCiszak\RuleEngine;

class Variable implements RuleElement, ValueElement
{
    use ValueAvailable;

    private function __construct(private readonly string $name, mixed $value)
    {
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): RuleElementType
    {
        return RuleElementType::VARIABLE;
    }

    public static function create(string $name, mixed $value = null): static
    {
        return new self($name, $value);
    }

    public function greaterThan(self $variable): Proposition
    {
        return Proposition::create(
            sprintf('%s_greaterThan_%s', $this->getName(), $variable->getName()),
            $this->value > $variable->value
        );
    }

    public function lessThan(self $variable): Proposition
    {
        return Proposition::create(
            sprintf('%s_lessThan_%s', $this->getName(), $variable->getName()),
            $this->value < $variable->value
        );
    }

    public function greaterThanOrEqualTo(self $variable): Proposition
    {
        return Proposition::create(
            sprintf('%s_greaterThanOrEqualTo_%s', $this->getName(), $variable->getName()),
            $this->value >= $variable->value
        );

    }

    public function lessThanOrEqualTo(self $variable): Proposition
    {
        return Proposition::create(
            sprintf('%s_lessThanOrEqualTo_%s', $this->getName(), $variable->getName()),
            $this->value <= $variable->value
        );
    }

    public function in(self $variable): Proposition
    {
        $value = !is_array($variable->value) ? [$variable->value] : $variable->value;
        return Proposition::create(
            sprintf('%s_in_%s', $this->getName(), $variable->getName()),
            in_array($this->value, $value, true)
        );
    }
}