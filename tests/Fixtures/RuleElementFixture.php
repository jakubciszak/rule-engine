<?php

namespace JakubCiszak\RuleEngine\Tests\Fixtures;

use JakubCiszak\RuleEngine\Proposition;

final readonly class RuleElementFixture
{
    public static function createFalseProposition(string $name = 'false_proposition'): Proposition
    {
        return Proposition::create($name, false);
    }

    public static function createTrueProposition(string $name = 'true_proposition'): Proposition
    {
        return Proposition::create($name);
    }

}