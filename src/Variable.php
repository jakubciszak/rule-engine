<?php
declare(strict_types=1);

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

    public function in(self $variable): Proposition
    {
        $value = !is_array($variable->value) ? [$variable->value] : $variable->value;
        return Proposition::create(
            sprintf('%s_in_%s', $this->getName(), $variable->getName()),
            in_array($this->value, $value, true)
        );
    }
}