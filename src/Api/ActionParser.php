<?php

namespace JakubCiszak\RuleEngine\Api;

use InvalidArgumentException;
use JakubCiszak\RuleEngine\{Action, ActionType, Variable};

final class ActionParser
{
    public static function parse(string $expression): Action
    {
        $expression = trim($expression);
        if (!preg_match('/^([\w\.]+)\s*([+\-=\.])\s*(.+)$/', $expression, $matches)) {
            throw new InvalidArgumentException('Invalid action expression');
        }

        $variable = self::parseVariable($matches[1]);
        $type = self::parseOperator($matches[2]);
        $value = self::parseValue($matches[3]);

        return new Action($type, $variable, $value);
    }

    private static function parseVariable(string $token): string
    {
        if (!str_starts_with($token, 'var.')) {
            throw new InvalidArgumentException('Action target must be a variable');
        }

        return substr($token, 4);
    }

    private static function parseOperator(string $token): ActionType
    {
        return match ($token) {
            '+' => ActionType::ADD,
            '-' => ActionType::SUBTRACT,
            '.' => ActionType::CONCAT,
            '=' => ActionType::SET,
            default => throw new InvalidArgumentException('Unknown action operator'),
        };
    }

    private static function parseValue(string $token): mixed
    {
        $token = trim($token);

        if (str_starts_with($token, 'var.')) {
            return Variable::create(substr($token, 4));
        }

        if (is_numeric($token)) {
            return $token + 0;
        }

        if (in_array($token, ['true', 'false'], true)) {
            return $token === 'true';
        }

        return $token;
    }
}
