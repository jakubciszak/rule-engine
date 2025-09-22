<?php

namespace JakubCiszak\RuleEngine\Api;

use JakubCiszak\RuleEngine\{Rule, RuleContext, Operator, Ruleset};
use InvalidArgumentException;

final class StringRuleApi
{
    private static int $constCounter = 0;

    private const PRECEDENCE = [
        'or' => 1,
        'and' => 2,
        'not' => 3,
        '!' => 3,
        '>' => 4,
        '<' => 4,
        '>=' => 4,
        '<=' => 4,
        'is' => 4,
        '==' => 4,
        '!=' => 4,
    ];

    private function __construct()
    {
    }

    /**
     * @param string|array<string, string> $expression
     * @param array<string, mixed> $data
     */
    public static function evaluate(string|array $expression, array $data = []): bool
    {
        $context = self::createContext($data);

        if (is_array($expression)) {
            $rules = array_reduce(
                array_keys($expression),
                static function (array $rules, string $name) use ($expression, $data): array {
                    $expr = $expression[$name];
                    $rule = new Rule($name);
                    self::parseExpression($expr, $rule, $data);
                    $rules[] = $rule;

                    return $rules;
                },
                []
            );

            return (new Ruleset(...$rules))->evaluate($context)->getValue();
        }

        $rule = new Rule('string_rule');
        self::parseExpression($expression, $rule, $data);

        return $rule->evaluate($context)->getValue();
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function parseExpression(string $expression, Rule $rule, array $data): void
    {
        $tokens = self::tokenize($expression);
        $rpn = self::toRpn($tokens);
        $engineRpn = self::toEngineOrder($rpn);

        array_walk(
            $engineRpn,
            static fn(string $token) => self::handleToken($token, $rule, $data)
        );
    }

    /**
     * @return string[]
     */
    private static function tokenize(string $expression): array
    {
        $replacements = ['(' => ' ( ', ')' => ' ) ', '!' => ' ! '];
        $prepared = strtr($expression, $replacements);
        return array_values(array_filter(explode(' ', trim($prepared)), static fn($t) => $t !== ''));
    }

    /**
     * @param string[] $tokens
     * @return string[]
     */
    private static function toRpn(array $tokens): array
    {
        $output = [];
        $stack = [];
        foreach ($tokens as $token) {
            $lower = strtolower($token);
            if ($token === '(') {
                $stack[] = $token;
                continue;
            }
            if ($token === ')') {
                while (!empty($stack) && end($stack) !== '(') {
                    $output[] = array_pop($stack);
                }
                array_pop($stack); // remove '('
                continue;
            }
            if (array_key_exists($lower, self::PRECEDENCE)) {
                while (!empty($stack)) {
                    $top = end($stack);
                    $topLower = strtolower($top);
                    if ($top !== '(' && (self::PRECEDENCE[$topLower] ?? 0) >= self::PRECEDENCE[$lower]) {
                        $output[] = array_pop($stack);
                        continue;
                    }
                    break;
                }
                $stack[] = $token;
                continue;
            }
            $output[] = $token;
        }
        while (!empty($stack)) {
            $output[] = array_pop($stack);
        }
        return $output;
    }

    /**
     * @param string[] $rpn
     * @return string[]
     */
    private static function toEngineOrder(array $rpn): array
    {
        $stack = [];
        foreach ($rpn as $token) {
            $lower = strtolower($token);
            if (array_key_exists($lower, self::PRECEDENCE)) {
                if ($lower === 'not' || $lower === '!') {
                    $operand = array_pop($stack);
                    $stack[] = array_merge($operand, [$token]);
                } else {
                    $right = array_pop($stack);
                    $left = array_pop($stack);
                    $stack[] = array_merge($right, $left, [$token]);
                }
            } else {
                $stack[] = [$token];
            }
        }

        return $stack[0] ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function handleToken(string $token, Rule $rule, array $data): void
    {
        $lower = strtolower($token);
        if (array_key_exists($lower, self::PRECEDENCE)) {
            $rule->addElement(self::mapOperator($lower));
            return;
        }
        if (str_starts_with($token, '.')) {
            self::addVariable(substr($token, 1), $rule, $data);
            return;
        }
        self::addConstant(self::parseValue($token), $rule);
    }

    private static function mapOperator(string $op): Operator
    {
        return Operator::create($op);
    }

    private static function parseValue(string $value): mixed
    {
        if (is_numeric($value)) {
            return $value + 0; // cast to int or float
        }
        $lower = strtolower($value);
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function addVariable(string $path, Rule $rule, array $data): void
    {
        $value = self::extractVar($data, $path);
        if (is_bool($value) || $value instanceof \Closure) {
            $rule->proposition($path, $value);
        } else {
            $rule->variable($path, $value);
        }
    }

    private static function addConstant(mixed $value, Rule $rule): void
    {
        $name = '#const' . ++self::$constCounter;
        $rule->variable($name, $value);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function extractVar(array $data, string $path): mixed
    {
        return array_reduce(
            explode('.', $path),
            static fn($carry, $part) => is_array($carry) && array_key_exists($part, $carry) ? $carry[$part] : null,
            $data
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function createContext(array $data): RuleContext
    {
        $context = new RuleContext();

        array_walk(
            $data,
            static function ($value, $name) use ($context): void {
                if (is_bool($value) || is_callable($value)) {
                    $context->proposition($name, $value);
                } else {
                    $context->variable($name, $value);
                }
            }
        );

        return $context;
    }
}
