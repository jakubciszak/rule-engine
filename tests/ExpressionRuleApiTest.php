<?php

declare(strict_types=1);

namespace JakubCiszak\RuleEngine\Tests;

use InvalidArgumentException;
use JakubCiszak\RuleEngine\Api\ExpressionRuleApi;
use JakubCiszak\RuleEngine\Expression\RuleExpressionLanguage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use UnexpectedValueException;

final class ExpressionRuleApiTest extends TestCase
{
    public function testEvaluatesNativeExpressionLanguageSyntax(): void
    {
        $expression = 'actualAge > 18 and citizenship === "PL"';
        $data = [
            'actualAge' => 22,
            'citizenship' => 'PL',
        ];

        self::assertTrue(ExpressionRuleApi::evaluate($expression, $data));
    }

    public function testEvaluatesEveryExpressionInNamedRuleset(): void
    {
        $expressions = [
            'adult' => 'age >= 18',
            'active' => 'status === "active"',
        ];

        self::assertTrue(ExpressionRuleApi::evaluate($expressions, [
            'age' => 20,
            'status' => 'active',
        ]));
        self::assertFalse(ExpressionRuleApi::evaluate($expressions, [
            'age' => 20,
            'status' => 'blocked',
        ]));
    }

    public function testEmptyRulesetUsesVacuousTruth(): void
    {
        self::assertTrue(ExpressionRuleApi::evaluate([]));
    }

    public function testSupportsQuotedStringsAndNotEqualOperator(): void
    {
        $expression = 'name === "John Doe" and status != "blocked"';

        self::assertTrue(ExpressionRuleApi::evaluate($expression, [
            'name' => 'John Doe',
            'status' => 'active',
        ]));
    }

    public function testSupportsNestedArraysAndStrictMembership(): void
    {
        $expression = 'customer["address"]["country"] === "PL" and customer["role"] in ["admin", "owner"]';
        $data = [
            'customer' => [
                'address' => ['country' => 'PL'],
                'role' => 'admin',
            ],
        ];

        self::assertTrue(ExpressionRuleApi::evaluate($expression, $data));
    }

    public function testKeepsStrictComparisonExplicit(): void
    {
        self::assertFalse(ExpressionRuleApi::evaluate('value === 1', ['value' => '1']));
        self::assertTrue(ExpressionRuleApi::evaluate('value == 1', ['value' => '1']));
    }

    public function testRejectsExpressionReturningNonBooleanValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('must evaluate to bool, int returned');

        ExpressionRuleApi::evaluate('amount + 1', ['amount' => 10]);
    }

    public function testLintRejectsUnknownVariables(): void
    {
        $this->expectException(SyntaxError::class);

        ExpressionRuleApi::lint('unknown > 1', ['known' => 1]);
    }

    public function testDefaultLanguageDoesNotExposePhpConstantFunction(): void
    {
        $this->expectException(SyntaxError::class);

        ExpressionRuleApi::lint('constant("PHP_VERSION") === "hidden"');
    }

    public function testRejectsObjectsInExpressionContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be scalar, null or array');

        ExpressionRuleApi::evaluate('customer != null', ['customer' => new \stdClass()]);
    }

    public function testIgnoresTopLevelKeysThatCannotBeExpressionVariables(): void
    {
        self::assertTrue(ExpressionRuleApi::evaluate('score === 123', [
            'conditions.0' => 'manual_review',
            'score' => 123,
        ]));
    }

    public function testAllowsExplicitlyRegisteredFunctions(): void
    {
        $provider = new class implements ExpressionFunctionProviderInterface {
            public function getFunctions(): array
            {
                return [ExpressionFunction::fromPhp('strlen', 'length')];
            }
        };
        $language = new RuleExpressionLanguage(null, [$provider]);

        self::assertTrue(ExpressionRuleApi::evaluate(
            'length(name) > 3',
            ['name' => 'Jakub'],
            $language,
        ));
    }
}
