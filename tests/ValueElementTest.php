<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Proposition;
use JakubCiszak\RuleEngine\ValueElement;
use JakubCiszak\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;

class ValueElementTest extends TestCase
{
    public function test_it_can_get_and_set_values(): void
    {
        $variable = Variable::create('test', 'initial_value');
        
        $this->assertEquals('initial_value', $variable->getValue());
        
        $variable->setValue('new_value');
        $this->assertEquals('new_value', $variable->getValue());
    }

    public function test_it_evaluates_equal_to_correctly(): void
    {
        $variable1 = Variable::create('var1', 5);
        $variable2 = Variable::create('var2', 5);
        $variable3 = Variable::create('var3', 10);

        $this->assertTrue($variable1->equalTo($variable2)->getValue());
        $this->assertFalse($variable1->equalTo($variable3)->getValue());
    }

    public function test_it_evaluates_not_equal_to_correctly(): void
    {
        $variable1 = Variable::create('var1', 5);
        $variable2 = Variable::create('var2', 5);
        $variable3 = Variable::create('var3', 10);

        $this->assertFalse($variable1->notEqualTo($variable2)->getValue());
        $this->assertTrue($variable1->notEqualTo($variable3)->getValue());
    }

    public function test_it_evaluates_greater_than_correctly(): void
    {
        $variable1 = Variable::create('var1', 10);
        $variable2 = Variable::create('var2', 5);
        $variable3 = Variable::create('var3', 15);

        $this->assertTrue($variable1->greaterThan($variable2)->getValue());
        $this->assertFalse($variable1->greaterThan($variable3)->getValue());
        $this->assertFalse($variable1->greaterThan($variable1)->getValue());
    }

    public function test_it_evaluates_less_than_correctly(): void
    {
        $variable1 = Variable::create('var1', 5);
        $variable2 = Variable::create('var2', 10);
        $variable3 = Variable::create('var3', 3);

        $this->assertTrue($variable1->lessThan($variable2)->getValue());
        $this->assertFalse($variable1->lessThan($variable3)->getValue());
        $this->assertFalse($variable1->lessThan($variable1)->getValue());
    }

    public function test_it_evaluates_greater_than_or_equal_to_correctly(): void
    {
        $variable1 = Variable::create('var1', 10);
        $variable2 = Variable::create('var2', 5);
        $variable3 = Variable::create('var3', 10);
        $variable4 = Variable::create('var4', 15);

        $this->assertTrue($variable1->greaterThanOrEqualTo($variable2)->getValue());
        $this->assertTrue($variable1->greaterThanOrEqualTo($variable3)->getValue());
        $this->assertFalse($variable1->greaterThanOrEqualTo($variable4)->getValue());
    }

    public function test_it_evaluates_less_than_or_equal_to_correctly(): void
    {
        $variable1 = Variable::create('var1', 5);
        $variable2 = Variable::create('var2', 10);
        $variable3 = Variable::create('var3', 5);
        $variable4 = Variable::create('var4', 3);

        $this->assertTrue($variable1->lessThanOrEqualTo($variable2)->getValue());
        $this->assertTrue($variable1->lessThanOrEqualTo($variable3)->getValue());
        $this->assertFalse($variable1->lessThanOrEqualTo($variable4)->getValue());
    }

    public function test_it_works_with_mixed_value_element_types(): void
    {
        $variable = Variable::create('var', 5);
        $proposition = Proposition::create('prop', true);

        // Test that we can compare different ValueElement implementations
        $this->assertInstanceOf(Proposition::class, $variable->equalTo($proposition));
        $this->assertInstanceOf(Proposition::class, $variable->notEqualTo($proposition));
    }

    public function test_it_handles_null_values_correctly(): void
    {
        $variable1 = Variable::create('var1', null);
        $variable2 = Variable::create('var2', null);
        $variable3 = Variable::create('var3', 5);

        $this->assertTrue($variable1->equalTo($variable2)->getValue());
        $this->assertFalse($variable1->equalTo($variable3)->getValue());
        $this->assertTrue($variable1->notEqualTo($variable3)->getValue());
    }

    public function test_all_comparison_methods_return_propositions(): void
    {
        $variable1 = Variable::create('var1', 5);
        $variable2 = Variable::create('var2', 10);

        $this->assertInstanceOf(Proposition::class, $variable1->equalTo($variable2));
        $this->assertInstanceOf(Proposition::class, $variable1->notEqualTo($variable2));
        $this->assertInstanceOf(Proposition::class, $variable1->greaterThan($variable2));
        $this->assertInstanceOf(Proposition::class, $variable1->lessThan($variable2));
        $this->assertInstanceOf(Proposition::class, $variable1->greaterThanOrEqualTo($variable2));
        $this->assertInstanceOf(Proposition::class, $variable1->lessThanOrEqualTo($variable2));
    }
}