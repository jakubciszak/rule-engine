<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Proposition;
use JakubCiszak\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;

class ValueElementIntegrationTest extends TestCase
{
    public function test_variable_can_compare_with_proposition(): void
    {
        $variable = Variable::create('var', 5);
        $proposition = Proposition::create('prop', true);
        
        // This should work now since both implement ValueElement
        $result = $variable->equalTo($proposition);
        $this->assertInstanceOf(Proposition::class, $result);
        
        $result2 = $variable->greaterThan($proposition);
        $this->assertInstanceOf(Proposition::class, $result2);
    }

    public function test_proposition_can_compare_with_variable(): void
    {
        $proposition = Proposition::create('prop', true);
        $variable = Variable::create('var', 5);
        
        // This should work now since both implement ValueElement
        $result = $proposition->equalTo($variable);
        $this->assertInstanceOf(Proposition::class, $result);
        
        $result2 = $proposition->lessThan($variable);
        $this->assertInstanceOf(Proposition::class, $result2);
    }

    public function test_comparison_methods_are_moved_to_trait(): void
    {
        $var1 = Variable::create('var1', 10);
        $var2 = Variable::create('var2', 5);
        
        // Test that comparison methods still work on Variable
        $this->assertTrue($var1->greaterThan($var2)->getValue());
        $this->assertFalse($var1->lessThan($var2)->getValue());
        $this->assertTrue($var1->greaterThanOrEqualTo($var2)->getValue());
        $this->assertFalse($var1->lessThanOrEqualTo($var2)->getValue());
        $this->assertFalse($var1->equalTo($var2)->getValue());
        $this->assertTrue($var1->notEqualTo($var2)->getValue());
    }
}