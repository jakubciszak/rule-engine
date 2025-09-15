<?php

namespace JakubCiszak\RuleEngine;

final readonly class Action
{
    public function __construct(
        private ActionType $type,
        private string $variable,
        private mixed $value
    ) {
    }

    public static function create(string $type, string $variable, mixed $value): self
    {
        return new self(ActionType::create($type), $variable, $value);
    }

    public function execute(RuleContext $context): void
    {
        $target = Variable::create($this->variable);
        $existing = $context->findElement($target);
        $current = $existing instanceof Variable ? $existing->getValue() : null;

        $value = $this->resolveValue($context);

        $newValue = match ($this->type) {
            ActionType::ADD => $this->handleAddition($current, $value),
            ActionType::SUBTRACT => ($current ?? 0) - $value,
            ActionType::CONCAT => (string)($current ?? '') . (string)$value,
            ActionType::SET => $value,
        };

        $context->variable($this->variable, $newValue);
    }

    private function handleAddition(mixed $current, mixed $value): mixed
    {
        if (null === $current || (is_numeric($current) && is_numeric($value))) {
            return ($current ?? 0) + $value;
        }

        if (is_array($current)) {
            if (is_array($value)) {
                return array_merge($current, $value);
            }

            $current[] = $value;
            return $current;
        }

        if ($current === null) {
            return is_array($value) ? $value : [$value];
        }

        return [$current, $value];
    }

    private function resolveValue(RuleContext $context): mixed
    {
        if ($this->value instanceof Variable) {
            $found = $context->findElement($this->value);
            return $found instanceof Variable ? $found->getValue() : null;
        }

        return $this->value;
    }
}
