<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

use Closure;

readonly class Proposition implements RuleElement, ValueElement
{
    use ValueAvailable;

    private function __construct(private string $name, null|Closure|bool $value)
    {
        $this->value = $value;
    }

    public static function success(): self
    {
        return self::create('success');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): RuleElementType
    {
        return RuleElementType::PROPOSITION;
    }

    public static function create(string $name, Closure|bool $value = true): static
    {
        return new self($name, $value);
    }

    public function and(self $proposition): Proposition
    {
        return self::create(
            sprintf('%s_and_%s', $this->getName(), $proposition->getName()),
            $this->value && $proposition->value
        );
    }

    public function or(self $proposition): Proposition
    {
        return self::create(
            sprintf('%s_or_%s', $this->getName(), $proposition->getName()),
            $this->value || $proposition->value
        );
    }

    public function not(): Proposition
    {
        return self::create(
            sprintf('not_%s', $this->getName()),
            !$this->getValue()
        );
    }

    public function getValue(): bool
    {
        if ($this->value instanceof Closure) {
            return call_user_func($this->value);
        }
        return $this->value;
    }
}