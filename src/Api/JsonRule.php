<?php

namespace JakubCiszak\RuleEngine\Api;

final class JsonRule
{
    private function __construct()
    {
    }

    /**
     * Apply JSON logic rules to provided data.
     *
     * @param array|string $rules
     * @param array|string $data
     *
     * @return mixed
     *
     * @throws \JsonException
     */
    public static function apply(array|string $rules, array|string $data = []): mixed
    {
        if (is_string($rules)) {
            $rules = json_decode($rules, true, 512, JSON_THROW_ON_ERROR);
        }

        if (is_string($data)) {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }

        return self::evaluate($rules, $data);
    }

    private static function evaluate(mixed $rule, array $data): mixed
    {
        if (is_array($rule)) {
            if (array_key_exists('var', $rule)) {
                return self::extractVar($data, $rule['var']);
            }

            if (count($rule) === 1) {
                $operator = array_key_first($rule);
                $values = (array) $rule[$operator];

                return match ($operator) {
                    'and' => self::evalAnd($values, $data),
                    'or' => self::evalOr($values, $data),
                    '!' => !self::evaluate($values[0], $data),
                    'not' => !self::evaluate($values[0], $data),
                    '==' => self::evaluate($values[0], $data) == self::evaluate($values[1], $data),
                    '!=' => self::evaluate($values[0], $data) != self::evaluate($values[1], $data),
                    '>' => self::evaluate($values[0], $data) > self::evaluate($values[1], $data),
                    '<' => self::evaluate($values[0], $data) < self::evaluate($values[1], $data),
                    '>=' => self::evaluate($values[0], $data) >= self::evaluate($values[1], $data),
                    '<=' => self::evaluate($values[0], $data) <= self::evaluate($values[1], $data),
                    default => null,
                };
            }
        }

        return $rule;
    }

    private static function evalAnd(array $values, array $data): bool
    {
        foreach ($values as $value) {
            if (!self::evaluate($value, $data)) {
                return false;
            }
        }

        return true;
    }

    private static function evalOr(array $values, array $data): bool
    {
        foreach ($values as $value) {
            if (self::evaluate($value, $data)) {
                return true;
            }
        }

        return false;
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
