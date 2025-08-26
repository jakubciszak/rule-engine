<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

trait ValueAvailable
{
    private mixed $value;

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function equalTo(ValueElement $element): Proposition
    {
        $elementName = $this->getElementName($element);
        $thisName = $this->getElementName($this);
        
        return Proposition::create(
            sprintf('%s_equalTo_%s', $thisName, $elementName),
            $this->getValue() === $element->getValue()
        );
    }

    public function notEqualTo(ValueElement $element): Proposition
    {
        $elementName = $this->getElementName($element);
        $thisName = $this->getElementName($this);
        
        return Proposition::create(
            sprintf('%s_notEqualTo_%s', $thisName, $elementName),
            $this->getValue() !== $element->getValue()
        );
    }

    public function greaterThan(ValueElement $element): Proposition
    {
        $elementName = $this->getElementName($element);
        $thisName = $this->getElementName($this);
        
        return Proposition::create(
            sprintf('%s_greaterThan_%s', $thisName, $elementName),
            $this->getValue() > $element->getValue()
        );
    }

    public function lessThan(ValueElement $element): Proposition
    {
        $elementName = $this->getElementName($element);
        $thisName = $this->getElementName($this);
        
        return Proposition::create(
            sprintf('%s_lessThan_%s', $thisName, $elementName),
            $this->getValue() < $element->getValue()
        );
    }

    public function greaterThanOrEqualTo(ValueElement $element): Proposition
    {
        $elementName = $this->getElementName($element);
        $thisName = $this->getElementName($this);
        
        return Proposition::create(
            sprintf('%s_greaterThanOrEqualTo_%s', $thisName, $elementName),
            $this->getValue() >= $element->getValue()
        );
    }

    public function lessThanOrEqualTo(ValueElement $element): Proposition
    {
        $elementName = $this->getElementName($element);
        $thisName = $this->getElementName($this);
        
        return Proposition::create(
            sprintf('%s_lessThanOrEqualTo_%s', $thisName, $elementName),
            $this->getValue() <= $element->getValue()
        );
    }

    private function getElementName(ValueElement $element): string
    {
        if (method_exists($element, 'getName')) {
            return $element->getName();
        }
        
        $className = get_class($element);
        $parts = explode('\\', $className);
        return end($parts);
    }
}