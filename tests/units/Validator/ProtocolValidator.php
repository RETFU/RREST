<?php

namespace RREST\tests\units\Validator;

require_once __DIR__ . '/../boostrap.php';

use atoum;

class ProtocolValidator extends atoum
{
    public function testOk()
    {
        $this
            ->given($this->newTestedInstance('https', ['http','HTTPS']))
            ->boolean($this->testedInstance->fails())
            ->isFalse()
        ;
    }

    public function testNotOk()
    {
        $this
            ->given($this->newTestedInstance('HTTP', ['HttPS']))
            ->boolean($this->testedInstance->fails())
            ->isTrue()
            ->exception($this->testedInstance->getException())
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException')
        ;
    }
}