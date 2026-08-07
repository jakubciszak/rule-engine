<?php

declare(strict_types=1);

namespace JakubCiszak\RuleEngine\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class RuleExpressionLanguage extends ExpressionLanguage
{
    /**
     * Keep the default rule language free from functions that can access PHP
     * constants or invoke arbitrary global functions. Applications can add
     * explicitly allowed functions through expression providers.
     */
    protected function registerFunctions(): void
    {
    }
}
