<?php

namespace JakubCiszak\RuleEngine;

enum ActionType
{
    case ADD;
    case SUBTRACT;
    case CONCAT;
    case SET;

    public static function create(string $name): self
    {
        return match (strtoupper($name)) {
            'ADD' => self::ADD,
            'SUBTRACT' => self::SUBTRACT,
            'CONCAT' => self::CONCAT,
            'SET' => self::SET,
            default => throw new \InvalidArgumentException("Unknown action type: $name"),
        };
    }
}
