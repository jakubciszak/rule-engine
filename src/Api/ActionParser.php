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
        if (!str_starts_with($token, '.')) {
            throw new InvalidArgumentException('Action target must be a variable');
        }

        return substr($token, 1);
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
        $result = $token;

        if (str_starts_with($token, '.')) {
            $result = Variable::create(substr($token, 1));
        } elseif (is_numeric($token)) {
            $result = $token + 0;
        } elseif (in_array($token, ['true', 'false'], true)) {
            $result = $token === 'true';
        }

        return $result;
    }
}
