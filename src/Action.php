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
            ActionType::ADD => $this->performAddition($current, $value),
            ActionType::SUBTRACT => $this->performSubtraction($current, $value),
            ActionType::CONCAT => $this->performConcatenation($current, $value),
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

    private function performAddition(mixed $current, mixed $value): float|int
    {
        $currentNum = is_numeric($current) ? (float)$current : 0;
        $valueNum = is_numeric($value) ? (float)$value : 0;
        
        return $currentNum + $valueNum;
    }

    private function performSubtraction(mixed $current, mixed $value): float|int
    {
        $currentNum = is_numeric($current) ? (float)$current : 0;
        $valueNum = is_numeric($value) ? (float)$value : 0;
        
        return $currentNum - $valueNum;
    }

    private function performConcatenation(mixed $current, mixed $value): string
    {
        $currentStr = $current !== null ? (is_scalar($current) ? (string)$current : '') : '';
        $valueStr = $value !== null ? (is_scalar($value) ? (string)$value : '') : '';
        
        return $currentStr . $valueStr;
    }
}
