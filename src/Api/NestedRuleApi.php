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
        // Flatten nested data to support wildcard expansion
        $flatData = self::flattenData($data);
        
        // Expand wildcards in rules
        $expandedRules = self::expandWildcards($rules, $flatData);
        
        $context = self::createContext($flatData);

        if (is_array($expandedRules) && self::isRulesetArray($expandedRules)) {
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
            
            // Merge flat data back to original structure and update reference
            $data = array_merge($data, $context->toArray());
            return $result;
        }

        if (is_array($expandedRules)) {
            $actions = self::extractActions($expandedRules);
        } else {
            $actions = [];
        }

        $rule = new Rule('json_rule');
        self::parseExpression($expandedRules, $rule, $flatData);

        $executor = $actions === [] ? $rule : self::decorateWithActions($rule, $actions);

        $result = $executor->evaluate($context)->getValue();
        
        // Merge flat data back to original structure and update reference
        $data = array_merge($data, $context->toArray());
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

    /**
     * Flatten nested array data to dotted notation
     */
    private static function flattenData(array $data, string $prefix = ''): array
    {
        $flattened = [];
        
        foreach ($data as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;
            
            if (is_array($value) && !empty($value)) {
                // Check if this is a numeric-indexed array (list)
                $isNumericArray = array_keys($value) === range(0, count($value) - 1);
                
                if ($isNumericArray) {
                    // Handle numeric arrays - flatten each element with index
                    foreach ($value as $index => $item) {
                        $indexedKey = $newKey . '.' . $index;
                        if (is_array($item)) {
                            $flattened = array_merge($flattened, self::flattenData($item, $indexedKey));
                        } else {
                            $flattened[$indexedKey] = $item;
                        }
                    }
                } else {
                    // Handle associative arrays - continue flattening
                    $flattened = array_merge($flattened, self::flattenData($value, $newKey));
                }
            } else {
                $flattened[$newKey] = $value;
            }
        }
        
        return $flattened;
    }

    /**
     * Expand wildcard patterns in rules
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
     * Find all wildcard paths in rule structure
     */
    private static function findWildcardPaths(array $rules, array &$paths = []): array
    {
        foreach ($rules as $key => $value) {
            if (is_array($value)) {
                if ($key === 'var' && is_string($value) && str_contains($value, '*')) {
                    $paths[] = $value;
                } elseif (is_string($key) && is_string($value) && str_contains($value, '*')) {
                    // Handle cases where var might be a direct value
                    $paths[] = $value;
                } else {
                    self::findWildcardPaths($value, $paths);
                }
            } elseif (is_string($value) && str_contains($value, '*')) {
                // Direct string value that contains wildcard
                $paths[] = $value;
            }
        }
        
        return $paths;
    }

    /**
     * Find keys in flat data that match a wildcard pattern
     */
    private static function findMatchingKeys(string $wildcardPath, array $flatData): array
    {
        $pattern = str_replace('.', '\.', $wildcardPath);
        $pattern = str_replace('*', '[0-9]+', $pattern);
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
     */
    private static function expandRuleForPath(array $rules, string $wildcardPath, array $concreteKeys): array
    {
        if (empty($concreteKeys)) {
            return $rules;
        }
        
        // For logical operators (and, or), we need to expand the rules
        foreach ($rules as $operator => $operands) {
            if ($operator === 'and' || $operator === 'or') {
                $expandedOperands = [];
                
                foreach ($operands as $operand) {
                    if (is_array($operand) && self::containsWildcardPath($operand, $wildcardPath)) {
                        // Expand this operand for each concrete key
                        foreach ($concreteKeys as $concreteKey) {
                            $expandedOperand = self::replaceWildcardInOperand($operand, $wildcardPath, $concreteKey);
                            $expandedOperands[] = $expandedOperand;
                        }
                    } else {
                        $expandedOperands[] = $operand;
                    }
                }
                
                $rules[$operator] = $expandedOperands;
            }
        }
        
        return $rules;
    }

    /**
     * Check if an operand contains a specific wildcard path
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
     */
    private static function containsWildcardPathRecursive(array $data, string $wildcardPath): bool
    {
        foreach ($data as $key => $value) {
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
     */
    private static function replaceWildcardInOperand(array $operand, string $wildcardPath, string $concreteKey): array
    {
        $result = $operand;
        
        // Recursively replace wildcard paths
        $result = self::replaceWildcardRecursive($result, $wildcardPath, $concreteKey);
        
        return $result;
    }

    /**
     * Recursively replace wildcard paths in nested arrays
     */
    private static function replaceWildcardRecursive(array $data, string $wildcardPath, string $concreteKey): array
    {
        foreach ($data as $key => &$value) {
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
