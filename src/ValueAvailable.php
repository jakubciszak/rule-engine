<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

trait ValueAvailable
{
    private readonly mixed $value;

    public function getValue(): mixed
    {
        return $this->value;
    }
    
    public function equalTo(ValueElement $element): Proposition
    {
        return Proposition::create(
            sprintf('%s_equalTo_%s', $this->getName(), $element->getName()),
            $this->value === $element->getValue()
        );
    }

    public function notEqualTo(ValueElement $element): Proposition
    {
        return Proposition::create(
            sprintf('%s_notEqualTo_%s', $this->getName(), $element->getName()),
            $this->value !== $element->getValue()
        );
    }
}