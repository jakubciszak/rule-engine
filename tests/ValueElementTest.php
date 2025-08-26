<?php
declare(strict_types=1);

namespace JakubCiszak\RuleEngine\Tests;

use JakubCiszak\RuleEngine\Proposition;
use JakubCiszak\RuleEngine\ValueElement;
use JakubCiszak\RuleEngine\Variable;
use PHPUnit\Framework\TestCase;

class ValueElementTest extends TestCase
{
    public function testVariableImplementsValueElement(): void
    {
        $variable = Variable::create('test', 'value');
        
        $this->assertInstanceOf(ValueElement::class, $variable);
    }
    
    public function testPropositionImplementsValueElement(): void
    {
        $proposition = Proposition::create('test', true);
        
        $this->assertInstanceOf(ValueElement::class, $proposition);
    }
    
    public function testValueElementGetValue(): void
    {
        $variable = Variable::create('test', 'value');
        $proposition = Proposition::create('test', true);
        
        $this->assertEquals('value', $variable->getValue());
        $this->assertTrue($proposition->getValue());
    }
    
    public function testValueElementSetValue(): void
    {
        $variable = Variable::create('test', 'initial');
        $variable->setValue('updated');
        
        $this->assertEquals('updated', $variable->getValue());
    }
    
    public function testValueElementEqualToMethod(): void
    {
        $variable1 = Variable::create('var1', 10);
        $variable2 = Variable::create('var2', 10);
        $variable3 = Variable::create('var3', 20);
        
        $result1 = $variable1->equalTo($variable2);
        $result2 = $variable1->equalTo($variable3);
        
        $this->assertInstanceOf(Proposition::class, $result1);
        $this->assertInstanceOf(Proposition::class, $result2);
        $this->assertTrue($result1->getValue());
        $this->assertFalse($result2->getValue());
    }
    
    public function testValueElementNotEqualToMethod(): void
    {
        $variable1 = Variable::create('var1', 10);
        $variable2 = Variable::create('var2', 10);
        $variable3 = Variable::create('var3', 20);
        
        $result1 = $variable1->notEqualTo($variable2);
        $result2 = $variable1->notEqualTo($variable3);
        
        $this->assertInstanceOf(Proposition::class, $result1);
        $this->assertInstanceOf(Proposition::class, $result2);
        $this->assertFalse($result1->getValue());
        $this->assertTrue($result2->getValue());
    }
    
    public function testVariableCanCompareWithProposition(): void
    {
        $variable = Variable::create('var', true);
        $proposition = Proposition::create('prop', true);
        
        $equalResult = $variable->equalTo($proposition);
        $notEqualResult = $variable->notEqualTo($proposition);
        
        $this->assertInstanceOf(Proposition::class, $equalResult);
        $this->assertInstanceOf(Proposition::class, $notEqualResult);
        $this->assertTrue($equalResult->getValue());
        $this->assertFalse($notEqualResult->getValue());
    }
    
    public function testPropositionCanCompareWithVariable(): void
    {
        $proposition = Proposition::create('prop', true);
        $proposition->setValue('test'); // Set value after creation
        $variable = Variable::create('var', 'test');
        
        $equalResult = $proposition->equalTo($variable);
        $notEqualResult = $proposition->notEqualTo($variable);
        
        $this->assertInstanceOf(Proposition::class, $equalResult);
        $this->assertInstanceOf(Proposition::class, $notEqualResult);
        $this->assertTrue($equalResult->getValue());
        $this->assertFalse($notEqualResult->getValue());
    }
    
    public function testPropositionEqualToAndNotEqualToMethods(): void
    {
        $prop1 = Proposition::create('prop1', true);
        $prop2 = Proposition::create('prop2', false);
        $prop3 = Proposition::create('prop3', true);
        
        // Test with boolean values that Proposition expects
        $equalResult = $prop1->equalTo($prop3);  // both have value true
        $notEqualResult = $prop1->notEqualTo($prop2);  // true vs false
        
        $this->assertInstanceOf(Proposition::class, $equalResult);
        $this->assertInstanceOf(Proposition::class, $notEqualResult);
        $this->assertTrue($equalResult->getValue());
        $this->assertTrue($notEqualResult->getValue());
    }
    
    public function testBackwardsCompatibilityWithVariableComparisons(): void
    {
        // Test that existing Variable comparison functionality still works
        $var1 = Variable::create('var1', 10);
        $var2 = Variable::create('var2', 10);
        $var3 = Variable::create('var3', 20);
        
        // These should work exactly as before
        $this->assertTrue($var1->equalTo($var2)->getValue());
        $this->assertFalse($var1->equalTo($var3)->getValue());
        $this->assertFalse($var1->notEqualTo($var2)->getValue());
        $this->assertTrue($var1->notEqualTo($var3)->getValue());
    }
}