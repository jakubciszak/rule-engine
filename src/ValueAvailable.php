<?php

namespace JakubCiszak\RuleEngine;

trait ValueAvailable
{
    private mixed $value;

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}