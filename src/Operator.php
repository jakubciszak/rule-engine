<?php

namespace JakubCiszak\RuleEngine;

enum Operator implements RuleElement
{
    case AND;
    case OR;
    case NOT;
    case EQUAL_TO;
    case NOT_EQUAL_TO;
    case GREATER_THAN;
    case LESS_THAN;
    case GREATER_THAN_OR_EQUAL_TO;
    case LESS_THAN_OR_EQUAL_TO;
    case IN;

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): RuleElementType
    {
        return RuleElementType::OPERATOR;
    }

    public static function create(string $name): static
    {
        return match ($name) {
            'AND' => self::AND,
            'OR' => self::OR,
            'NOT' => self::NOT,
            'EQUAL_TO' => self::EQUAL_TO,
            'NOT_EQUAL_TO' => self::NOT_EQUAL_TO,
            'GREATER_THAN' => self::GREATER_THAN,
            'LESS_THAN' => self::LESS_THAN,
            'GREATER_THAN_OR_EQUAL_TO' => self::GREATER_THAN_OR_EQUAL_TO,
            'LESS_THAN_OR_EQUAL_TO' => self::LESS_THAN_OR_EQUAL_TO,
            'IN' => self::IN,
        };
    }

    public function toOperationName(): string
    {
        return lcfirst(str_replace('_', '', ucwords(strtolower($this->name), '_')));
    }
}