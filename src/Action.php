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
            ActionType::SUBTRACT => $this->handleSubtraction($current, $value),
            ActionType::CONCAT => $this->handleConcatenation($current, $value),
            ActionType::SET => $value,
        };

        $context->variable($this->variable, $newValue);
    }

    private function handleAddition(mixed $current, mixed $value): mixed
    {
        if (null === $current || (is_numeric($current) && is_numeric($value))) {
            return ($current ?? 0) + (is_numeric($value) ? $value : 0);
        }

        if (is_array($current)) {
            if (is_array($value)) {
                return array_merge($current, $value);
            }

            $current[] = $value;
            return $current;
        }

        return [$current, $value];
    }

    private function handleSubtraction(mixed $current, mixed $value): mixed
    {
        if (is_numeric($current) && is_numeric($value)) {
            return $current - $value;
        }
        $currentNum = is_numeric($current) ? $current : 0;
        $valueNum = is_numeric($value) ? $value : 0;
        return $currentNum - $valueNum;
    }

    private function handleConcatenation(mixed $current, mixed $value): string
    {
        $currentStr = match (true) {
            is_string($current) => $current,
            is_numeric($current) => (string)$current,
            $current === null => '',
            is_bool($current) => $current ? 'true' : 'false',
            is_array($current) => json_encode($current),
            default => ''
        };
        
        $valueStr = match (true) {
            is_string($value) => $value,
            is_numeric($value) => (string)$value,
            $value === null => '',
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => json_encode($value),
            default => ''
        };
        
        return $currentStr . $valueStr;
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
