<?php

namespace RREST\tests\units\Validator\Util\JsonGuardValidationErrorConverter;

require_once __DIR__ . '/../../../boostrap.php';

use atoum;
use League\JsonGuard\Validator;
use RREST\Error;

class TypeConverter extends atoum
{
    public function testGetErrors()
    {
        $json = json_decode('{"artist":5}');
        $schema = json_decode('{
          "$schema": "http://json-schema.org/schema",
          "type": "object",
          "properties": {
            "artist": { "type": "string" }
          },
          "required": [ "artist" ]
        }');
        $validator = new Validator($json, $schema);
        $error = $validator->errors()[0];

        $this
            ->given($this->newTestedInstance($error))
            ->array($this->testedInstance->getErrors())
            ->hasSize(1)
            ->values
            ->object[0]->isInstanceOf('\RREST\Error')
            ->string($this->testedInstance->getErrors()[0]->code)->isEqualTo(Error::DATA_VALIDATION_TYPE);
    }
}