<?php

namespace JakubCiszak\RuleEngine\Api;

use JakubCiszak\RuleEngine\{Rule, RuleContext, Operator};

final class JsonRule
{
    private static int $constCounter = 0;

    private function __construct()
    {
    }

    /**
     * Apply JSON logic rules to provided data.
     *
     * @param array|string $rules
     * @param array|string $data
     *
     * @throws \JsonException
     */
    public static function apply(array|string $rules, array|string $data = []): bool
    {
        if (is_string($rules)) {
            $rules = json_decode($rules, true, 512, JSON_THROW_ON_ERROR);
        }

        if (is_string($data)) {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }

        $context = new RuleContext();
        $rule = new Rule('json_rule');

        self::parseExpression($rules, $rule, $context, $data);

        $result = $rule->evaluate($context);

        return $result->isRight()
            ? $result->get()->getValue()
            : $result->getLeft()->getValue();
    }

    private static function parseExpression(mixed $expr, Rule $rule, RuleContext $context, array $data): void
    {
        if (is_array($expr)) {
            if (array_key_exists('var', $expr)) {
                self::addVariable($expr['var'], $rule, $context, $data);
                return;
            }

            if (count($expr) === 1) {
                $operator = array_key_first($expr);
                $values = (array) $expr[$operator];

                match ($operator) {
                    'and', 'or' => self::parseLogical($operator, $values, $rule, $context, $data),
                    '!', 'not' => self::parseNot($values[0], $rule, $context, $data),
                    '==', '!=', '>', '<', '>=', '<=', 'in' => self::parseComparison($operator, $values, $rule, $context, $data),
                    default => self::addConstant($expr, $rule, $context)
                };
                return;
            }
        }

        if (!is_null($expr)) {
            self::addConstant($expr, $rule, $context);
        }
    }

    private static function parseLogical(string $operator, array $values, Rule $rule, RuleContext $context, array $data): void
    {
        $first = true;
        foreach ($values as $value) {
            self::parseExpression($value, $rule, $context, $data);
            if ($first) {
                $first = false;
                continue;
            }
            $rule->addElement(Operator::create(strtoupper($operator)));
        }
    }

    private static function parseNot(mixed $value, Rule $rule, RuleContext $context, array $data): void
    {
        self::parseExpression($value, $rule, $context, $data);
        $rule->addElement(Operator::NOT);
    }

    private static function parseComparison(string $operator, array $values, Rule $rule, RuleContext $context, array $data): void
    {
        self::parseExpression($values[1] ?? null, $rule, $context, $data);
        self::parseExpression($values[0] ?? null, $rule, $context, $data);

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

    private static function addVariable(string $path, Rule $rule, RuleContext $context, array $data): void
    {
        $rule->variable($path);
        $context->variable($path, self::extractVar($data, $path));
    }

    private static function addConstant(mixed $value, Rule $rule, RuleContext $context): void
    {
        $name = '#const' . ++self::$constCounter;
        $rule->variable($name);
        $context->variable($name, $value);
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
}
