<?php

namespace JakubCiszak\RuleEngine\Api;

use InvalidArgumentException;
use JakubCiszak\RuleEngine\{Rule, RuleContext, Operator, Variable, Proposition, Action, ActivityRule, RuleInterface, Ruleset};
use JakubCiszak\RuleEngine\Api\ActionParser;

final class FlatRuleAPI
{
    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $rulesetData
     * @param array<string, mixed> $contextData
     * @param-out array<string, mixed> $contextData
     */
    public static function evaluate(array $rulesetData, array &$contextData = []): bool
    {

        if (!isset($rulesetData['rules']) || !is_array($rulesetData['rules'])) {
            throw new InvalidArgumentException('Invalid rules data');
        }

        $rulesData = $rulesetData['rules'];
        foreach ($rulesData as $ruleData) {
            if (!is_array($ruleData)) {
                throw new InvalidArgumentException('Each rule must be an array');
            }
        }

        $context = self::createContext($contextData);

        $rules = array_map(
            /** @param array<string, mixed> $ruleData */
            static fn(array $ruleData): RuleInterface => self::createRule($ruleData),
            $rulesData
        );

        $ruleset = new Ruleset(...$rules);

        $result = $ruleset->evaluate($context)->getValue();
        $contextData = $context->toArray();
        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function createRule(array $data): RuleInterface
    {
        $name = $data['name'] ?? uniqid('rule_', true);
        if (!is_string($name)) {
            throw new InvalidArgumentException('Rule name must be a string');
        }
        
        $rule = new Rule($name);
        $elements = $data['elements'] ?? [];
        
        if (!is_array($elements)) {
            throw new InvalidArgumentException('Rule elements must be an array');
        }
        
        foreach ($elements as $element) {
            if (!is_array($element)) {
                throw new InvalidArgumentException('Rule element must be an array');
            }
            
            $type = $element['type'] ?? null;
            $elementName = $element['name'] ?? null;
            
            if (!is_string($type) || !is_string($elementName)) {
                throw new InvalidArgumentException('Element type and name must be strings');
            }
            
            if ($type === 'operator') {
                $rule->addElement(Operator::create($elementName));
                continue;
            }
            if ($type === 'variable') {
                $rule->addElement(Variable::create($elementName, $element['value'] ?? null));
                continue;
            }
            if ($type === 'proposition') {
                $value = $element['value'] ?? true;
                if (!is_bool($value) && !$value instanceof \Closure) {
                    throw new InvalidArgumentException('Proposition value must be bool or Closure');
                }
                $rule->addElement(Proposition::create($elementName, $value));
                continue;
            }
            throw new InvalidArgumentException('Invalid rule element');
        }
        $resultRule = $rule;

        if (!empty($data['actions']) && is_array($data['actions'])) {
            $actionExpressions = $data['actions'];
            foreach ($actionExpressions as $expr) {
                if (!is_string($expr)) {
                    throw new InvalidArgumentException('Action expressions must be strings');
                }
            }
            
            $actions = array_map(
                /** @param string $expr */
                static fn(string $expr): Action => ActionParser::parse($expr),
                $actionExpressions
            );

            $activity = static function (RuleContext $context) use ($actions): void {
                foreach ($actions as $action) {
                    $action->execute($context);
                }
            };

            $resultRule = new ActivityRule($rule, $activity);
        }

        return $resultRule;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function createContext(array $data): RuleContext
    {
        $context = new RuleContext();
        foreach ($data as $name => $value) {
            if (is_bool($value) || is_callable($value)) {
                $context->proposition($name, $value);
            } else {
                $context->variable($name, $value);
            }
        }
        return $context;
    }
}
