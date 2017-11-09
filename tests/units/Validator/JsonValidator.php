<?php

namespace RREST\tests\units\Validator;

require_once __DIR__.'/../boostrap.php';

use atoum;

class JsonValidator extends atoum
{
    public function testFails()
    {
        $jsonValidator= new JsonValidator('{}', '{}');

        $this
            ->given($jsonValidator)
            ->boolean($this->testedInstance->fails())
            ->isFalse();
    }
}