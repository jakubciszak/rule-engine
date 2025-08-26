<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

interface RuleElement
{
    public function getName(): string;
    public function getType(): RuleElementType;

    public static function create(string $name): static;
}