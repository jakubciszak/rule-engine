<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

interface ValueElement
{
    public function getValue(): mixed;

    public function setValue(mixed $value): void;

    public function equalTo(ValueElement $element): Proposition;

    public function notEqualTo(ValueElement $element): Proposition;

    public function greaterThan(ValueElement $element): Proposition;

    public function lessThan(ValueElement $element): Proposition;

    public function greaterThanOrEqualTo(ValueElement $element): Proposition;

    public function lessThanOrEqualTo(ValueElement $element): Proposition;
}