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
}
