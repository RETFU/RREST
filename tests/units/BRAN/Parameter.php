<?php
namespace RREST\tests\units;

require_once __DIR__ . '/../boostrap.php';

use atoum;

class Parameter extends atoum
{
    public function testGetName()
    {
        $this
            ->given($this->newTestedInstance('name','string',true))
            ->string($this->testedInstance->getName())
            ->isEqualTo('name')
        ;
    }

    public function testGetType()
    {
        $this
            ->given($this->newTestedInstance('name','string',true))
            ->string($this->testedInstance->getType())
            ->isEqualTo('string')
        ;
    }

    public function testSetType()
    {
        $this
            ->exception(
                function() {
                    $this->newTestedInstance('name','float',true);
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('float')
        ;
    }

    public function testAssertValue()
    {
        //Required
        $this->newTestedInstance('name','string',true);
        $this
            ->boolean($this->testedInstance->getRequired())
            ->isTrue();
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue(null, null)
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;

        //cast type string
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue(50, '50')
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue(true, 'true')
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;

        //cast type integer
        $this->newTestedInstance('name','integer',true);
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue(50.5, '50.5')
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;

        //cast type number
        $this->newTestedInstance('name','number',true);
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue('test', 'test')
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;

        //cast type boolean
        $this->newTestedInstance('name','boolean',true);
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue('test', 'test')
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;

        //cast type date
        $this->newTestedInstance('name','date',true);
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue('test', 'test')
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;

        //min
        $this->newTestedInstance('name','integer',true);
        $this->testedInstance->setMinimum(50);
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue(30, 30)
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;
        //max
        $this->testedInstance->setMaximum(100);
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue(120, 120)
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;

        //enum
        $this->newTestedInstance('name','integer',true);
        $this->testedInstance->setEnum([50,100,200]);
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue(120, 120)
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;

        //pattern
        $this->newTestedInstance('name','string',true);
        $this->testedInstance->setValidationPattern('^\w+@[a-zA-Z_]+?.[a-zA-Z]{2,3}$');
        $this
            ->exception(
                function() {
                    $this
                        ->testedInstance
                        ->assertValue('not an email', 'not an email')
                    ;
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;
    }
}
