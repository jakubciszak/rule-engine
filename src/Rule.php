<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine;

class Rule implements RuleInterface
{
    /** @var array<RuleElement> */
    private array $elements;

    public function __construct(public readonly string $name)
    {
        $this->elements = [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<RuleElement> */
    public function elements(): array
    {
        return $this->elements;
    }

    public function and(): self
    {
        return $this->addElement(Operator::AND);
    }

    public function or(): self
    {
        return $this->addElement(Operator::OR);
    }

    public function not(): self
    {
        return $this->addElement(Operator::NOT);
    }

    public function equalTo(): self
    {
        return $this->addElement(Operator::EQUAL_TO);
    }

    public function notEqualTo(): self
    {
        return $this->addElement(Operator::NOT_EQUAL_TO);
    }

    public function greaterThan(): self
    {
        return $this->addElement(Operator::GREATER_THAN);
    }

    public function lessThan(): self
    {
        return $this->addElement(Operator::LESS_THAN);
    }

    public function greaterThanOrEqualTo(): self
    {
        return $this->addElement(Operator::GREATER_THAN_OR_EQUAL_TO);
    }

    public function lessThanOrEqualTo(): self
    {
        return $this->addElement(Operator::LESS_THAN_OR_EQUAL_TO);
    }

    public function in(): self
    {
        return $this->addElement(Operator::IN);
    }
    
    public function addElement(RuleElement $element): self
    {
        $this->elements[] = $element;
        return $this;
    }

    public function evaluate(RuleContext $context): Proposition
    {
        return $this->process($this->elements, $context);
    }

    public function variable(string $name, mixed $value = null): self
    {
        return $this->addElement(Variable::create($name, $value));
    }

    public function proposition(string $name, null|\Closure|bool $value = true): self
    {
        return $this->addElement(Proposition::create($name, $value ?? true));
    }

    private function isPropositionOrVariable(RuleElement $element): bool
    {
        return $element->getType()->isOneOf(RuleElementType::PROPOSITION, RuleElementType::VARIABLE);
    }

    /**
     * @param array<RuleElement> $elements
     */
    private function process(array $elements, RuleContext $context): Proposition
    {
        /** @var array<RuleElement> $stack */
        $stack = [];
        foreach ($elements as $ruleElement) {
            $this->processRuleElement($stack, $ruleElement, $context);
        }
        $result = array_shift($stack);
        // After processing, the top of stack should be a Proposition
        return $result instanceof Proposition ? $result : Proposition::success();
    }

    /**
     * @param array<RuleElement> $stack
     */
    private function processRuleElement(array &$stack, RuleElement $ruleElement, RuleContext $context): bool
    {
        if ($this->isOperator($ruleElement)) {
            /** @var Operator $ruleElement */
            $this->processOperator($stack, $ruleElement);
        } elseif ($this->isPropositionOrVariable($ruleElement)) {
            /** @var Proposition|Variable $ruleElement */
            $this->processPropositionOrVariable($stack, $ruleElement, $context);
        }
        return true;
    }

    private function isOperator(RuleElement $ruleElement): bool
    {
        return $ruleElement->getType()->isOneOf(RuleElementType::OPERATOR);
    }

    /**
     * @param array<RuleElement> $stack
     */
    private function processOperator(array &$stack, Operator $ruleElement): void
    {
        $this->invokePredicate($stack, $ruleElement);
    }

    /**
     * @param array<RuleElement> $stack
     */
    private function processPropositionOrVariable(array &$stack, RuleElement $ruleElement, RuleContext $context): void
    {
        // Check if there's an element with the same name in the context
        $contextElement = $context->findElement($ruleElement);
        if ($contextElement !== null) {
            // Use the element from context as it has the canonical value
            $stack[] = $contextElement;
        } else {
            // Use the original element if not found in context
            $stack[] = $ruleElement;
        }
    }

    /**
     * @param array<RuleElement> $stack
     */
    private function invokePredicate(array &$stack, Operator $operator): void
    {
        if ($operator === Operator::NOT) {
            /** @var Proposition $element */
            $element = array_pop($stack);
            array_push($stack, $element->not());
        } else {
            /** @var Proposition $leftElement */
            $leftElement = array_pop($stack);
            /** @var Proposition $rightElement */
            $rightElement = array_pop($stack);
            $operation = $operator->toOperationName();
            array_push($stack, $leftElement->$operation($rightElement));
        }
    }
}