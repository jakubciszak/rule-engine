<?php

namespace JakubCiszak\RuleEngine;

use InvalidArgumentException;

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

    public static function create(string $symbol): static
    {
        return match (strtolower($symbol)) {
            'and' => self::AND,
            'or' => self::OR,
            'not', '!' => self::NOT,
            '==', 'is', 'equal_to' => self::EQUAL_TO,
            '!=', 'not_equal_to' => self::NOT_EQUAL_TO,
            '>' , 'greater_than' => self::GREATER_THAN,
            '<', 'less_than' => self::LESS_THAN,
            '>=' , 'greater_than_or_equal_to' => self::GREATER_THAN_OR_EQUAL_TO,
            '<=', 'less_than_or_equal_to' => self::LESS_THAN_OR_EQUAL_TO,
            'in' => self::IN,
            default => throw new InvalidArgumentException('Unsupported operator'),
        };
    }

    public function toOperationName(): string
    {
        return lcfirst(str_replace('_', '', ucwords(strtolower($this->name), '_')));
    }
}
