<?php

namespace JakubCiszak\RuleEngine\Api;

use JakubCiszak\RuleEngine\{Rule, RuleContext, Operator, Ruleset, Action, ActivityRule, RuleInterface};
use JakubCiszak\RuleEngine\Api\ActionParser;

final class NestedRuleApi
{
    private static int $constCounter = 0;
    private const array OPERATORS = ['and', 'or', '!', 'not', '==', '!=', '>', '<', '>=', '<=', 'in'];

    private function __construct()
    {
        // we want to prevent instantiation
    }

    /**
     * @param array<mixed> $rules
     * @param array<string, mixed> $data
     */
    public static function evaluate(array $rules, array &$data = []): bool
    {
        $flatData = self::flattenData($data);
        $expandedRules = self::expandWildcards($rules, $flatData);
        $context = self::createContext($flatData);

        if (self::isRulesetArray($expandedRules)) {
            $ruleObjects = array_map(
                static function (string $name) use ($expandedRules, $flatData): RuleInterface {
                    $definition = $expandedRules[$name];
                    $actions = self::extractActions($definition);

                    $rule = new Rule($name);
                    self::parseExpression($definition, $rule, $flatData);

                    return $actions === [] ? $rule : self::decorateWithActions($rule, $actions);
                },
                array_keys($expandedRules)
            );

            $ruleset = new Ruleset(...$ruleObjects);
            $result = $ruleset->evaluate($context)->getValue();
            
            $data = array_merge($data, $context->toArray());
            return $result;
        }

        $actions = self::extractActions($expandedRules);

        $rule = new Rule('json_rule');
        self::parseExpression($expandedRules, $rule, $flatData);

        $executor = $actions === [] ? $rule : self::decorateWithActions($rule, $actions);

        $result = $executor->evaluate($context)->getValue();
        
        // Merge flat data back to original structure and update reference
        $data = array_merge($data, $context->toArray());
        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @param array<mixed> $values
     * @param array<string, mixed> $data
     */
    private static function parseLogical(string $operator, array $values, Rule $rule, array $data): void
    {
        foreach ($values as $index => $value) {
            self::parseExpression($value, $rule, $data);
            if ($index > 0) {
                $rule->addElement(Operator::create($operator));
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function parseNot(mixed $value, Rule $rule, array $data): void
    {
        self::parseExpression($value, $rule, $data);
        $rule->addElement(Operator::NOT);
    }

    /**
     * @param array<mixed> $values
     * @param array<string, mixed> $data
     */
    private static function parseComparison(string $operator, array $values, Rule $rule, array $data): void
    {
        self::parseExpression($values[1] ?? null, $rule, $data);
        self::parseExpression($values[0] ?? null, $rule, $data);

        $rule->addElement(Operator::create($operator));
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

    /**
     * @param array<mixed> $rules
     */
    private static function isRulesetArray(array $rules): bool
    {
        if (empty($rules)) {
            return false;
        }

        if (count($rules) === 1) {
            $key = array_key_first($rules);
            return !in_array($key, self::OPERATORS, true);
        }

        return true;
    }

    /**
     * @param array<mixed> $definition
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

    /**
     * Flatten nested array data to dotted notation
     * Cognitive Complexity reduced: no nested ifs, no deep nesting, single recursion point
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function flattenData(array $data, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $flatKey = $prefix === '' ? $key : $prefix . '.' . $key;
            if (!is_array($value) || empty($value)) {
                $result[$flatKey] = $value;
                continue;
            }
            if (array_is_list($value)) {
                foreach ($value as $i => $item) {
                    $indexedKey = $flatKey . '.' . $i;
                    $result += self::flattenData($item, $indexedKey);
                }
                continue;
            }
            $result += self::flattenData($value, $flatKey);
        }
        return $result;
    }

    /**
     * Expand wildcard patterns in rules
     * @param array<mixed> $rules
     * @param array<string, mixed> $flatData
     * @return array<mixed>
     */
    private static function expandWildcards(array $rules, array $flatData): array
    {
        // Deep copy rules to avoid modifying original
        $expandedRules = $rules;
        
        // Find all paths that need expansion
        $wildcardPaths = self::findWildcardPaths($expandedRules);
        
        if (empty($wildcardPaths)) {
            return $expandedRules;
        }
        
        // For each wildcard path, find matching keys and expand
        foreach ($wildcardPaths as $wildcardPath) {
            $matchingKeys = self::findMatchingKeys($wildcardPath, $flatData);
            $expandedRules = self::expandRuleForPath($expandedRules, $wildcardPath, $matchingKeys);
        }
        
        return $expandedRules;
    }

    /**
     * Find all wildcard paths in rule structure.
     *
     * @param array<mixed> $rules
     * @param array<string> $paths
     *
     * @return string[]
     */
    private static function findWildcardPaths(array $rules, array &$paths = []): array
    {
        foreach ($rules as $value) {
            if (is_array($value)) {
                self::findWildcardPaths($value, $paths);
            } elseif (is_string($value) && str_contains($value, '*')) {
                // Direct string value that contains wildcard
                $paths[] = $value;
            }
        }
        
        return $paths;
    }

    /**
     * Find keys in flat data that match a wildcard pattern
     * @param array<string, mixed> $flatData
     * @return array<string>
     */
    private static function findMatchingKeys(string $wildcardPath, array $flatData): array
    {
        $pattern = str_replace(['.', '*'], ['\.', '[0-9]+'], $wildcardPath);
        $pattern = '/^' . $pattern . '$/';
        
        $matchingKeys = [];
        foreach (array_keys($flatData) as $key) {
            if (preg_match($pattern, $key)) {
                $matchingKeys[] = $key;
            }
        }
        
        return $matchingKeys;
    }

    /**
     * Expand a rule structure to handle multiple concrete paths instead of wildcard
     * Cognitive Complexity reduced: helper for expanding operands, early returns, no deep nesting
     * @param array<mixed> $rules
     * @param array<string> $concreteKeys
     * @return array<mixed>
     */
    private static function expandRuleForPath(array $rules, string $wildcardPath, array $concreteKeys): array
    {
        if (empty($concreteKeys)) {
            return $rules;
        }
        foreach ($rules as $operator => $operands) {
            if ($operator !== 'and' && $operator !== 'or') {
                continue;
            }
            $rules[$operator] = self::expandOperands($operands, $wildcardPath, $concreteKeys);
        }
        return $rules;
    }

    /**
     * Helper to expand operands for logical operators
     * @param array<mixed> $operands
     * @param array<string> $concreteKeys
     * @return array<mixed>
     */
    private static function expandOperands(array $operands, string $wildcardPath, array $concreteKeys): array
    {
        $expanded = [];
        foreach ($operands as $operand) {
            if (is_array($operand) && self::containsWildcardPath($operand, $wildcardPath)) {
                foreach ($concreteKeys as $concreteKey) {
                    $expanded[] = self::replaceWildcardInOperand($operand, $wildcardPath, $concreteKey);
                }
            } else {
                $expanded[] = $operand;
            }
        }
        return $expanded;
    }

    /**
     * Check if an operand contains a specific wildcard path
     * @param array<mixed> $operand
     */
    private static function containsWildcardPath(array $operand, string $wildcardPath): bool
    {
        if (isset($operand['var']) && $operand['var'] === $wildcardPath) {
            return true;
        }
        
        // Recursively check nested arrays for wildcard patterns
        return self::containsWildcardPathRecursive($operand, $wildcardPath);
    }

    /**
     * Recursively search for wildcard path in nested arrays
     * @param array<mixed> $data
     */
    private static function containsWildcardPathRecursive(array $data, string $wildcardPath): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                if (isset($value['var']) && $value['var'] === $wildcardPath) {
                    return true;
                }
                // Recursively check deeper
                if (self::containsWildcardPathRecursive($value, $wildcardPath)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Replace wildcard path with concrete path in an operand
     * @param array<mixed> $operand
     * @return array<mixed>
     */
    private static function replaceWildcardInOperand(array $operand, string $wildcardPath, string $concreteKey): array
    {
        $result = $operand;

        // Recursively replace wildcard paths
        return self::replaceWildcardRecursive($result, $wildcardPath, $concreteKey);
    }

    /**
     * Recursively replace wildcard paths in nested arrays
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private static function replaceWildcardRecursive(array $data, string $wildcardPath, string $concreteKey): array
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                if (isset($value['var']) && $value['var'] === $wildcardPath) {
                    $value['var'] = $concreteKey;
                } else {
                    $value = self::replaceWildcardRecursive($value, $wildcardPath, $concreteKey);
                }
            }
        }
        
        return $data;
    }
}
