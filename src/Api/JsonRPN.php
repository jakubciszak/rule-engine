<?php

namespace JakubCiszak\RuleEngine\Api;

use InvalidArgumentException;
use JakubCiszak\RuleEngine\{Rule, RuleContext, Operator, Variable, Proposition, Action, ActivityRule, RuleInterface};
use JakubCiszak\RuleEngine\Api\ActionParser;

final class JsonRPN
{
    private function __construct()
    {
    }

    /**
     * @param string $rulesetJson JSON describing rules in RPN notation
     * @param string $contextJson JSON describing context variables and propositions
     *
     * @throws \JsonException
     */
    public static function evaluate(string $rulesetJson, string $contextJson = '{}'): string
    {
        $rulesData = json_decode($rulesetJson, true, 512, JSON_THROW_ON_ERROR);
        $contextData = json_decode($contextJson, true, 512, JSON_THROW_ON_ERROR);

        if (!isset($rulesData['rules']) || !is_array($rulesData['rules'])) {
            throw new InvalidArgumentException('Invalid rules JSON');
        }

        $context = self::createContext($contextData);
        $results = [];

        foreach ($rulesData['rules'] as $ruleData) {
            $rule = self::createRule($ruleData);
            $result = $rule->evaluate($context);
            $results[] = ['name' => $rule->name, 'value' => $result->getValue()];
        }

        return json_encode(['results' => $results], JSON_THROW_ON_ERROR);
    }

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

    private static function createContext(array $data): RuleContext
    {
        $context = new RuleContext();
        foreach ($data as $name => $value) {
            if (is_bool($value)) {
                $context->proposition($name, $value);
            } else {
                $context->variable($name, $value);
            }
        }
        return $context;
    }
}
