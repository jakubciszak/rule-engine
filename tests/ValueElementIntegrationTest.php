<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Proposition;
use JakubCiszak\RuleEngine\ValueElement;
use JakubCiszak\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;

/**
 * Integration test to verify the ValueElement interface implementation meets all requirements
 */
class ValueElementIntegrationTest extends TestCase
{
    public function testRequirementImplementation(): void
    {
        // Requirement: Variable and Proposition should implement ValueElement interface
        $variable = Variable::create('testVar', 100);
        $proposition = Proposition::create('testProp', true);
        
        $this->assertInstanceOf(ValueElement::class, $variable);
        $this->assertInstanceOf(ValueElement::class, $proposition);
        
        // Requirement: getValue and setValue methods should be available
        $this->assertEquals(100, $variable->getValue());
        $this->assertTrue($proposition->getValue());
        
        $variable->setValue(200);
        $this->assertEquals(200, $variable->getValue());
        
        // Requirement: equalTo and notEqualTo methods should accept ValueElement interface
        $var1 = Variable::create('var1', 'test');
        $var2 = Variable::create('var2', 'test');
        $prop1 = Proposition::create('prop1', true);
        
        // Variable comparing with Variable (using ValueElement interface)
        $result1 = $var1->equalTo($var2);
        $this->assertInstanceOf(Proposition::class, $result1);
        $this->assertTrue($result1->getValue());
        
        // Variable comparing with Proposition (using ValueElement interface)  
        // Use boolean value for Proposition since that's what it's designed for
        $prop1 = Proposition::create('prop1', false);
        $var_bool = Variable::create('var_bool', false);
        $result2 = $var_bool->equalTo($prop1);
        $this->assertInstanceOf(Proposition::class, $result2);
        $this->assertTrue($result2->getValue());
        
        // Proposition comparing with Variable (using ValueElement interface)
        $result3 = $prop1->notEqualTo($var1);
        $this->assertInstanceOf(Proposition::class, $result3);
        $this->assertTrue($result3->getValue()); // false !== 'test' is true
        
        // Test notEqualTo method
        $var3 = Variable::create('var3', 'different');
        $result4 = $var1->notEqualTo($var3);
        $this->assertInstanceOf(Proposition::class, $result4);
        $this->assertTrue($result4->getValue());
    }
    
    public function testMethodsMovedFromVariableToValueAvailable(): void
    {
        // Verify that equalTo and notEqualTo were moved from Variable to ValueAvailable trait
        // and are now available on both Variable and Proposition
        
        $variable = Variable::create('var', 'value');
        $proposition = Proposition::create('prop', true);
        $proposition->setValue('value');
        
        // Both should have the methods from ValueAvailable trait
        $this->assertTrue(method_exists($variable, 'equalTo'));
        $this->assertTrue(method_exists($variable, 'notEqualTo'));
        $this->assertTrue(method_exists($proposition, 'equalTo'));
        $this->assertTrue(method_exists($proposition, 'notEqualTo'));
        
        // Methods should work cross-type with appropriate data types
        $var_true = Variable::create('var_true', true);
        $prop_true = Proposition::create('prop_true', true);
        $equalResult = $var_true->equalTo($prop_true);
        $this->assertTrue($equalResult->getValue());
    }
}