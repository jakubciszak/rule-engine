<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

class RuleContext
{
    /** @var array<string, RuleElement> */
    private array $elements;

    public function __construct()
    {
        $this->elements = [];
    }

    public function addElement(RuleElement $ruleElement): self
    {
        $this->elements[$ruleElement->getName()] = $ruleElement;
        return $this;
    }

    public function variable(string $name, mixed $value = null): self
    {
        return $this->addElement(Variable::create($name, $value));
    }

    public function proposition(string $name, mixed $value = null): self
    {
        return $this->addElement(Proposition::create($name, $value));
    }

    public function append(RuleContext $context): self
    {
        $newContext = new RuleContext();
        $newContext->setElements(array_merge($this->elements, $context->elements));
        return $newContext;
    }

    /**
     * @param array<string, RuleElement> $elements
     */
    private function setElements(array $elements): void
    {
        $this->elements = $elements;
    }

    public function findElement(RuleElement $ruleElement): ?RuleElement
    {
        return $this->elements[$ruleElement->getName()] ?? null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->elements as $name => $element) {
            if (method_exists($element, 'getValue')) {
                $result[$name] = $element->getValue();
            }
        }

        return $result;
    }
}