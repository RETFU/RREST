<?php

namespace RREST\tests\units\Validator\Util\JsonGuardValidationErrorConverter;

require_once __DIR__ . '/../../../boostrap.php';

use atoum;
use League\JsonGuard\Constraint\DraftFour\Required;

class Converter extends atoum
{
    public function testGetErrors()
    {
        $this
            ->given($this->newTestedInstance(
                new \League\JsonGuard\ValidationError('a', 'b', 'c', 'd', 'e', 'f', 'g')
            ))
            ->array($this->testedInstance->getErrors())
            ->hasSize(1)
            ->values
            ->object[0]->isInstanceOf('\RREST\Error');

        $this
            ->given($this->newTestedInstance(
                new \League\JsonGuard\ValidationError('a', Required::KEYWORD, 'c', ['d'], 'e', 'f', 'g')
            ))
            ->array($this->testedInstance->getErrors())
            ->hasSize(1)
        ;
    }
}
