<?php

declare(strict_types=1);

namespace JakubCiszak\RuleEngine\Api;

use InvalidArgumentException;
use JakubCiszak\RuleEngine\{Action, RuleContext};
use JakubCiszak\RuleEngine\Expression\RuleExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use UnexpectedValueException;

final class ExpressionRuleApi
{
    private static ?RuleExpressionLanguage $defaultLanguage = null;

    private function __construct()
    {
    }

    /**
     * @param string|array<string, string> $expression
     * @param array<string, mixed> $data
     * @param list<string> $actions
     * @param-out array<string, mixed> $data
     */
    public static function evaluate(
        string|array $expression,
        array &$data = [],
        ?ExpressionLanguage $language = null,
        array $actions = [],
    ): bool {
        $context = self::prepareContext($data);
        $language ??= self::defaultLanguage();
        $parsedActions = self::parseActions($actions);

        if (is_string($expression)) {
            $result = self::evaluateExpression($expression, $context, $language);
        } else {
            $result = true;
            foreach ($expression as $name => $ruleExpression) {
                self::assertNamedExpression($name, $ruleExpression);

                if (!self::evaluateExpression($ruleExpression, $context, $language, $name)) {
                    $result = false;
                }
            }
        }

        if ($result) {
            self::executeActions($parsedActions, $data);
        }

        return $result;
    }

    /**
     * @param string|array<string, string> $expression
     * @param array<string, mixed> $data
     * @param list<string> $actions
     */
    public static function lint(
        string|array $expression,
        array $data = [],
        ?ExpressionLanguage $language = null,
        array $actions = [],
    ): void {
        $context = self::prepareContext($data);
        $language ??= self::defaultLanguage();
        $variableNames = array_keys($context);
        self::parseActions($actions);

        if (is_string($expression)) {
            $language->lint($expression, $variableNames);
            return;
        }

        foreach ($expression as $name => $ruleExpression) {
            self::assertNamedExpression($name, $ruleExpression);
            $language->lint($ruleExpression, $variableNames);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function evaluateExpression(
        string $expression,
        array $data,
        ExpressionLanguage $language,
        ?string $ruleName = null,
    ): bool {
        $result = $language->evaluate($expression, $data);

        if (!is_bool($result)) {
            $subject = $ruleName === null ? 'Expression rule' : sprintf('Expression rule "%s"', $ruleName);

            throw new UnexpectedValueException(sprintf(
                '%s must evaluate to bool, %s returned.',
                $subject,
                get_debug_type($result),
            ));
        }

        return $result;
    }

    private static function assertNamedExpression(mixed $name, mixed $expression): void
    {
        if (!is_string($name) || !is_string($expression)) {
            throw new InvalidArgumentException('Named expressions must use string names and string expressions.');
        }
    }

    /**
     * @param array<mixed> $actions
     * @return list<Action>
     */
    private static function parseActions(array $actions): array
    {
        $parsed = [];

        foreach ($actions as $action) {
            if (!is_string($action)) {
                throw new InvalidArgumentException('Action expression must be a string.');
            }

            $parsed[] = ActionParser::parse($action);
        }

        return $parsed;
    }

    /**
     * @param list<Action> $actions
     * @param array<string, mixed> $data
     * @param-out array<string, mixed> $data
     */
    private static function executeActions(array $actions, array &$data): void
    {
        if ($actions === []) {
            return;
        }

        $context = new RuleContext();
        foreach ($data as $name => $value) {
            $context->variable($name, $value);
        }

        foreach ($actions as $action) {
            $action->execute($context);
        }

        $data = $context->toArray();
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<string, mixed>
     */
    private static function prepareContext(array $data): array
    {
        $context = [];

        foreach ($data as $name => $value) {
            if (!is_string($name) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name) !== 1) {
                continue;
            }

            self::assertSafeValue($value, $name);
            $context[$name] = $value;
        }

        return $context;
    }

    private static function assertSafeValue(mixed $value, string $path): void
    {
        if ($value === null || is_scalar($value)) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                self::assertSafeValue($nestedValue, sprintf('%s[%s]', $path, (string) $key));
            }
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Expression context value "%s" must be scalar, null or array, %s given.',
            $path,
            get_debug_type($value),
        ));
    }

    private static function defaultLanguage(): RuleExpressionLanguage
    {
        return self::$defaultLanguage ??= new RuleExpressionLanguage();
    }
}
