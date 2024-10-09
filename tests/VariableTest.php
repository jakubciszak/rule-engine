<?php

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;

class VariableTest extends TestCase
{
    public function testShouldCreate(): void
    {
        $variable = Variable::create('someVariable', 1);
        $this->assertEquals(1, $variable->getValue());
        $this->assertEquals('someVariable', $variable->getName());
    }

    public function testCompare(): void
    {
        $variable1 = Variable::create('variable1', 1);
        $variable2 = Variable::create('variable2', 2);
        $variable3 = Variable::create('variable2', 2);


        $this->assertFalse($variable1->equalTo($variable2)->getValue());
        $this->assertTrue($variable2->equalTo($variable3)->getValue());
        $this->assertTrue($variable1->notEqualTo($variable2)->getValue());
        $this->assertFalse($variable1->greaterThan($variable2)->getValue());
        $this->assertTrue($variable1->lessThan($variable2)->getValue());
        $this->assertTrue($variable2->lessThanOrEqualTo($variable3)->getValue());
        $this->assertTrue($variable1->lessThanOrEqualTo($variable2)->getValue());
    }

    public function testCompareWithNull(): void
    {
        $variable1 = Variable::create('variable1', 1);
        $variable2 = Variable::create('variable2');

        $this->assertFalse($variable1->equalTo($variable2)->getValue());
        $this->assertTrue($variable1->notEqualTo($variable2)->getValue());
        $this->assertTrue($variable1->greaterThan($variable2)->getValue());
        $this->assertFalse($variable1->lessThan($variable2)->getValue());
        $this->assertFalse($variable1->lessThanOrEqualTo($variable2)->getValue());
        $this->assertTrue($variable1->greaterThanOrEqualTo($variable2)->getValue());
    }

    public function testVariableIn(): void
    {
        $valid = Variable::create('variable1', 1);
        $invalid = Variable::create('variable1', 10);
        $variable2 = Variable::create('variable2', [1, 2, 3]);

        $this->assertTrue($valid->in($variable2)->getValue());
        $this->assertFalse($invalid->in($variable2)->getValue());
    }
}