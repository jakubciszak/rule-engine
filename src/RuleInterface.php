<?php

namespace JakubCiszak\RuleEngine;

use Closure;

interface RuleInterface
{
    public function getName(): string;
    public function and(): self;
    public function or(): self;
    public function not(): self;
    public function equalTo(): self;
    public function notEqualTo(): self;
    public function greaterThan(): self;
    public function lessThan(): self;
    public function greaterThanOrEqualTo(): self;
    public function lessThanOrEqualTo(): self;
    public function in(): self;
    public function addElement(RuleElement $element): self;
    public function evaluate(RuleContext $context): Proposition;
    public function variable(string $name, mixed $value = null): self;
    public function proposition(string $name, null|Closure|bool $value = true): self;
}
