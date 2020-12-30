<?php

namespace RREST\tests\units\Validator;

require_once __DIR__ . '/../boostrap.php';

use atoum;

class ContentTypeValidator extends atoum
{
    public function testOk()
    {
        $this
            ->given($this->newTestedInstance('application/json', ['application/json','text/html']))
            ->boolean($this->testedInstance->fails())
            ->isFalse()
        ;
    }

    public function testNotOk()
    {
        $this
            ->given($this->newTestedInstance('application/json', ['text/html']))
            ->boolean($this->testedInstance->fails())
            ->isTrue()
            ->exception($this->testedInstance->getException())
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException')
        ;
    }

    public function testMultipart()
    {
        $this
            ->given($this->newTestedInstance('multipart/form-data; boundary=--------------------------699519696930389418481751', ['multipart/form-data']))
            ->boolean($this->testedInstance->fails())
            ->isFalse()
        ;
    }
}
