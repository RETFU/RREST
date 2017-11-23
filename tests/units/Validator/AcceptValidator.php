<?php

namespace RREST\tests\units\Validator;

require_once __DIR__ . '/../boostrap.php';

use atoum;

class AcceptValidator extends atoum
{
    public function testOk()
    {
        $this
            ->given($this->newTestedInstance('application/json', ['application/json','text/html']))
            ->boolean($this->testedInstance->fails())
            ->isFalse()
            ->string($this->testedInstance->getBestAccept())
            ->isEqualTo('application/json')
        ;
    }

    public function testEmptyAccept()
    {
        $this
            ->given($this->newTestedInstance('', ['application/json']))
            ->boolean($this->testedInstance->fails())
            ->isFalse()
            ->variable($this->testedInstance->getBestAccept())
            ->isNull()
        ;

        $this
            ->given($this->newTestedInstance(null, ['application/json']))
            ->boolean($this->testedInstance->fails())
            ->isFalse()
            ->variable($this->testedInstance->getBestAccept())
            ->isNull()
        ;
    }

    public function testBadAccept()
    {
        $this
            ->given($this->newTestedInstance('fuck', ['application/json']))
            ->boolean($this->testedInstance->fails())
            ->isTrue()
            ->string($this->testedInstance->getBestAccept())
            ->isEqualTo('fuck')
        ;
    }

    public function testNonAcceptableAccept()
    {
        $this
            ->given($this->newTestedInstance('text/html', ['application/json']))
            ->boolean($this->testedInstance->fails())
            ->isTrue()
            ->string($this->testedInstance->getBestAccept())
            ->isEqualTo('text/html')
            ->exception($this->testedInstance->getException())
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException')
        ;
    }

    public function testNoContentTypeDefined()
    {
        $this
            ->exception(
                function() {
                    $this->newTestedInstance('application/json', []);
                }
            )
        ;

        $this
            ->exception(
                function() {
                    $this->newTestedInstance('application/json', null);
                }
            )
        ;

        $this
            ->exception(
                function() {
                    $this->newTestedInstance(null, null);
                }
            )
        ;


        $this
            ->exception(
                function() {
                    $this->newTestedInstance(null, []);
                }
            )
        ;
    }
}