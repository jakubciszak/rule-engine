<?php

namespace JakubCiszak\RuleEngine\Api;

use JakubCiszak\RuleEngine\{Rule, RuleContext, Operator, Ruleset};

final class JsonRule
{
    private static int $constCounter = 0;
    private const OPERATORS = ['and', 'or', '!', 'not', '==', '!=', '>', '<', '>=', '<=', 'in'];

    private function __construct()
    {
    }

    /**
     * Evaluate JSON logic rules using provided data.
     *
     * @param array|string $rules
     * @param array|string $data
     *
     * @throws \JsonException
     */
    public static function evaluate(array|string $rules, array|string $data = []): bool
    {
        if (is_string($rules)) {
            $rules = json_decode($rules, true, 512, JSON_THROW_ON_ERROR);
        }

        if (is_string($data)) {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }

        $context = new RuleContext();

        if (is_array($rules) && self::isRulesetArray($rules)) {
            $ruleObjects = array_map(
                static function (string $name) use ($rules, $data): Rule {
                    $rule = new Rule($name);
                    self::parseExpression($rules[$name], $rule, $data);

                    return $rule;
                },
                array_keys($rules)
            );

            $ruleset = new Ruleset(...$ruleObjects);
            $result = $ruleset->evaluate($context);
            return $result->isRight();
        }

        $rule = new Rule('json_rule');
        self::parseExpression($rules, $rule, $data);
        $result = $rule->evaluate($context);
        return $result->isRight();
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
                    $rule->addElement(Operator::create(strtoupper($operator)));
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

        $op = match ($operator) {
            '==' => Operator::EQUAL_TO,
            '!=' => Operator::NOT_EQUAL_TO,
            '>' => Operator::GREATER_THAN,
            '<' => Operator::LESS_THAN,
            '>=' => Operator::GREATER_THAN_OR_EQUAL_TO,
            '<=' => Operator::LESS_THAN_OR_EQUAL_TO,
            'in' => Operator::IN,
        };

        $rule->addElement($op);
    }

    private static function addVariable(string $path, Rule $rule, array $data): void
    {
        $rule->variable($path, self::extractVar($data, $path));
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
}
