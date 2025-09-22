<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

interface ValueElement
{
    public function getValue(): mixed;

    public function getName(): string;

    public function equalTo(ValueElement $element): Proposition;
    
    public function notEqualTo(ValueElement $element): Proposition;
}