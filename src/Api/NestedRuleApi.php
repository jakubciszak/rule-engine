<?php

namespace JakubCiszak\RuleEngine\Api;

use JakubCiszak\RuleEngine\{Rule, RuleContext, Operator, Ruleset, Action, ActivityRule, RuleInterface};
use JakubCiszak\RuleEngine\Api\ActionParser;

final class NestedRuleApi
{
    private static int $constCounter = 0;
    private const OPERATORS = ['and', 'or', '!', 'not', '==', '!=', '>', '<', '>=', '<=', 'in'];

    private function __construct()
    {
    }

    public static function evaluate(array $rules, array &$data = []): bool
    {
        $context = self::createContext($data);

        if (is_array($rules) && self::isRulesetArray($rules)) {
            $ruleObjects = array_map(
                static function (string $name) use ($rules, $data): RuleInterface {
                    $definition = $rules[$name];
                    $actions = self::extractActions($definition);

                    $rule = new Rule($name);
                    self::parseExpression($definition, $rule, $data);

                    return $actions === [] ? $rule : self::decorateWithActions($rule, $actions);
                },
                array_keys($rules)
            );

            $ruleset = new Ruleset(...$ruleObjects);
            $result = $ruleset->evaluate($context)->getValue();
            $data = $context->toArray();
            return $result;
        }

        if (is_array($rules)) {
            $actions = self::extractActions($rules);
        } else {
            $actions = [];
        }

        $rule = new Rule('json_rule');
        self::parseExpression($rules, $rule, $data);

        $executor = $actions === [] ? $rule : self::decorateWithActions($rule, $actions);

        $result = $executor->evaluate($context)->getValue();
        $data = $context->toArray();
        return $result;
    }

    private static function parseExpression(mixed $expr, Rule $rule, array $data): void
    {
        if (is_array($expr)) {
            if (array_key_exists('var', $expr)) {
                self::addVariable($expr['var'], $rule, $data);
                return;
            }

            if (count($expr) === 1) {
                $operator = array_key_first($expr);
                $values = (array) $expr[$operator];

                match ($operator) {
                    'and', 'or' => self::parseLogical($operator, $values, $rule, $data),
                    '!', 'not' => self::parseNot($values[0], $rule, $data),
                    '==', '!=', '>', '<', '>=', '<=', 'in' => self::parseComparison($operator, $values, $rule, $data),
                    default => self::addConstant($expr, $rule)
                };
                return;
            }
        }

        if (!is_null($expr)) {
            self::addConstant($expr, $rule);
        }
    }

    private static function parseLogical(string $operator, array $values, Rule $rule, array $data): void
    {
        array_map(
            static function (mixed $value, int $index) use ($operator, $rule, $data): void {
                self::parseExpression($value, $rule, $data);

                if ($index > 0) {
                    $rule->addElement(Operator::create($operator));
                }
            },
            $values,
            array_keys($values)
        );
    }

    private static function parseNot(mixed $value, Rule $rule, array $data): void
    {
        self::parseExpression($value, $rule, $data);
        $rule->addElement(Operator::NOT);
    }

    private static function parseComparison(string $operator, array $values, Rule $rule, array $data): void
    {
        self::parseExpression($values[1] ?? null, $rule, $data);
        self::parseExpression($values[0] ?? null, $rule, $data);

        $rule->addElement(Operator::create($operator));
    }

    private static function addVariable(string $path, Rule $rule, array $data): void
    {
        $value = self::extractVar($data, $path);
        if (is_bool($value) || is_callable($value)) {
            $rule->proposition($path, $value ?? true);
        } else {
            $rule->variable($path, $value);
        }
    }

    private static function addConstant(mixed $value, Rule $rule): void
    {
        $name = '#const' . ++self::$constCounter;
        $rule->variable($name, $value);
    }

    private static function extractVar(array $data, string $path): mixed
    {
        $parts = explode('.', $path);
        $value = $data;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }

    private static function isRulesetArray(array $rules): bool
    {
        if (count($rules) === 0) {
            return false;
        }

        if (count($rules) === 1) {
            $key = array_key_first($rules);
            return !in_array($key, self::OPERATORS, true);
        }

        return true;
    }

    /**
     * @return string[]
     */
    private static function extractActions(array &$definition): array
    {
        $actions = [];

        if (isset($definition['actions']) && is_array($definition['actions'])) {
            $actions = $definition['actions'];
            unset($definition['actions']);
        }

        return $actions;
    }

    /**
     * @param string[] $actions
     */
    private static function decorateWithActions(Rule $rule, array $actions): RuleInterface
    {
        $parsed = array_map(
            static fn(string $expr): Action => ActionParser::parse($expr),
            $actions
        );

        $activity = static function (RuleContext $context) use ($parsed): void {
            foreach ($parsed as $action) {
                $action->execute($context);
            }
        };

        return new ActivityRule($rule, $activity);
    }

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
