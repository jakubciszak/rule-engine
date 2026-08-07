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

        self::assertTrue(ExpressionRuleApi::evaluate($expression, $data)->getResult());
    }

    public function testEvaluatesEveryExpressionInNamedRuleset(): void
    {
        $expressions = [
            'adult' => 'age >= 18',
            'active' => 'status === "active"',
        ];
        $activeAdult = [
            'age' => 20,
            'status' => 'active',
        ];
        $blockedAdult = [
            'age' => 20,
            'status' => 'blocked',
        ];

        self::assertTrue(ExpressionRuleApi::evaluate($expressions, $activeAdult)->getResult());
        self::assertFalse(ExpressionRuleApi::evaluate($expressions, $blockedAdult)->getResult());
    }

    public function testEmptyRulesetUsesVacuousTruth(): void
    {
        self::assertTrue(ExpressionRuleApi::evaluate([])->getResult());
    }

    public function testSupportsQuotedStringsAndNotEqualOperator(): void
    {
        $expression = 'name === "John Doe" and status != "blocked"';
        $data = [
            'name' => 'John Doe',
            'status' => 'active',
        ];

        self::assertTrue(ExpressionRuleApi::evaluate($expression, $data)->getResult());
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

        self::assertTrue(ExpressionRuleApi::evaluate($expression, $data)->getResult());
    }

    public function testKeepsStrictComparisonExplicit(): void
    {
        $strictData = ['value' => '1'];
        $looseData = ['value' => '1'];

        self::assertFalse(ExpressionRuleApi::evaluate('value === 1', $strictData)->getResult());
        self::assertTrue(ExpressionRuleApi::evaluate('value == 1', $looseData)->getResult());
    }

    public function testRejectsExpressionReturningNonBooleanValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('must evaluate to bool, int returned');
        $data = ['amount' => 10];

        ExpressionRuleApi::evaluate('amount + 1', $data);
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
        $data = ['customer' => new \stdClass()];

        ExpressionRuleApi::evaluate('customer != null', $data);
    }

    public function testIgnoresTopLevelKeysThatCannotBeExpressionVariables(): void
    {
        $data = [
            'conditions.0' => 'manual_review',
            'score' => 123,
        ];

        self::assertTrue(ExpressionRuleApi::evaluate('score === 123', $data)->getResult());
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
        $data = ['name' => 'Jakub'];

        self::assertTrue(ExpressionRuleApi::evaluate(
            'length(name) > 3',
            $data,
            $language,
        )->getResult());
    }

    public function testExecutesActionsWhenExpressionMatches(): void
    {
        $data = [
            'eligible' => true,
            'count' => 0,
            'status' => 'pending',
        ];

        $result = ExpressionRuleApi::evaluate(
            expression: 'eligible',
            data: $data,
            actions: [
                '.count + 1',
                '.status = approved',
            ],
        );

        self::assertTrue($result->getResult());
        self::assertSame(0, $data['count']);
        self::assertSame('pending', $data['status']);
        self::assertSame(1, $result->getContext()['count']);
        self::assertSame('approved', $result->getContext()['status']);
    }

    public function testDoesNotExecuteActionsWhenExpressionDoesNotMatch(): void
    {
        $data = [
            'eligible' => false,
            'count' => 0,
        ];

        $result = ExpressionRuleApi::evaluate(
            expression: 'eligible',
            data: $data,
            actions: ['.count + 1'],
        );

        self::assertFalse($result->getResult());
        self::assertSame(0, $data['count']);
        self::assertSame(0, $result->getContext()['count']);
    }

    public function testExecutesActionsOnceAfterEntireNamedRulesetMatches(): void
    {
        $data = [
            'age' => 20,
            'status' => 'active',
            'count' => 0,
        ];

        $result = ExpressionRuleApi::evaluate(
            expression: [
                'adult' => 'age >= 18',
                'active' => 'status === "active"',
            ],
            data: $data,
            actions: ['.count + 1'],
        );

        self::assertTrue($result->getResult());
        self::assertSame(0, $data['count']);
        self::assertSame(1, $result->getContext()['count']);
    }

    public function testActionsCanReferenceContextAndEarlierActionResults(): void
    {
        $data = [
            'eligible' => true,
            'count' => 1,
            'increment' => 2,
        ];

        $result = ExpressionRuleApi::evaluate(
            expression: 'eligible',
            data: $data,
            actions: [
                '.count + .increment',
                '.total + .count',
            ],
        );

        self::assertTrue($result->getResult());
        self::assertSame(1, $data['count']);
        self::assertArrayNotHasKey('total', $data);
        self::assertSame(3, $result->getContext()['count']);
        self::assertSame(3, $result->getContext()['total']);
    }

    public function testValidatesActionsBeforeEvaluatingExpression(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Action target must be a variable');
        $data = ['eligible' => false];

        ExpressionRuleApi::evaluate(
            expression: 'eligible',
            data: $data,
            actions: ['count + 1'],
        );
    }
}
