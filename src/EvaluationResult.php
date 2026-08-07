<?php

declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

final readonly class EvaluationResult
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public bool $result,
        public array $context,
    ) {
    }
}
