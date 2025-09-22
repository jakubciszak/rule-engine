<?php

declare(strict_types=1);

namespace JakubCiszak\RuleEngine\Api;

use InvalidArgumentException;
use JakubCiszak\RuleEngine\{Rule, RuleContext, Operator, Variable, Proposition, Action, ActivityRule, RuleInterface, Ruleset};
use JakubCiszak\RuleEngine\Api\ActionParser;

final class FlatRuleAPI
{
    private function __construct()
    {
        // we want to prevent creating instances of this class
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

        $context = self::createContext($contextData);

        $rules = array_map(
            static fn(array $ruleData): RuleInterface => self::createRule($ruleData),
            $rulesetData['rules']
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
        $rule = new Rule($data['name'] ?? uniqid('rule_', true));
        foreach ($data['elements'] ?? [] as $element) {
            $type = $element['type'] ?? null;
            $name = $element['name'] ?? null;
            if ($type === 'operator') {
                $rule->addElement(Operator::create($name));
                continue;
            }
            if ($type === 'variable') {
                $rule->addElement(Variable::create($name, $element['value'] ?? null));
                continue;
            }
            if ($type === 'proposition') {
                $rule->addElement(Proposition::create($name, $element['value'] ?? true));
                continue;
            }
            throw new InvalidArgumentException('Invalid rule element');
        }
        $resultRule = $rule;

        if (!empty($data['actions']) && is_array($data['actions'])) {
            $actions = array_map(
                static fn(string $expr): Action => ActionParser::parse($expr),
                $data['actions']
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
