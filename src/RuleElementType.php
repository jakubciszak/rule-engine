<?php

namespace JakubCiszak\RuleEngine;

enum RuleElementType
{
    case OPERATOR;
    case PROPOSITION;
    case VARIABLE;

    public function isOneOf(self $type, self ...$others): bool
    {
        return in_array($this, [$type, ...$others], true);
    }
}
