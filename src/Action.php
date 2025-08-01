<?php

namespace JakubCiszak\RuleEngine;

final class Action
{
    public function __construct(
        private readonly ActionType $type,
        private readonly string $variable,
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
            ActionType::ADD => ($current ?? 0) + $value,
            ActionType::SUBTRACT => ($current ?? 0) - $value,
            ActionType::CONCAT => (string)($current ?? '') . (string)$value,
            ActionType::SET => $value,
        };

        $context->variable($this->variable, $newValue);
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
