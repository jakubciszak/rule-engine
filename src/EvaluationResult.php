<?php

declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

final class EvaluationResult
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly bool $result,
        private readonly array $context,
    ) {
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
