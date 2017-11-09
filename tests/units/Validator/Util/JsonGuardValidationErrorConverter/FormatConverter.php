<?php

namespace RREST\tests\units\Validator\Util\JsonGuardValidationErrorConverter;

require_once __DIR__ . '/../../../boostrap.php';

use atoum;
use League\JsonGuard\Validator;
use RREST\Error;

class FormatConverter extends atoum
{
    public function testGetErrors()
    {
        //email
        $schema = json_decode('{
          "$schema": "http://json-schema.org/schema",
          "type": "object",
          "properties": {
            "artist": { "type": "string", "format": "email" }
          },
          "required": [ "artist" ]
        }');
        $json = json_decode('{"artist":"c"}');
        $validator = new Validator($json, $schema);
        $error = $validator->errors()[0];

        $this
            ->given($this->newTestedInstance($error))
            ->array($this->testedInstance->getErrors())
            ->hasSize(1)
            ->values
            ->object[0]->isInstanceOf('\RREST\Error')
            ->string($this->testedInstance->getErrors()[0]->code)->isEqualTo(Error::DATA_VALIDATION_FORMAT);

        //uri
        $schema = json_decode('{
          "$schema": "http://json-schema.org/schema",
          "type": "object",
          "properties": {
            "artist": { "type": "string", "format": "uri" }
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
            ->object[0]->isInstanceOf('\RREST\Error');

        //ipv4
        $schema = json_decode('{
          "$schema": "http://json-schema.org/schema",
          "type": "object",
          "properties": {
            "artist": { "type": "string", "format": "ipv4" }
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
            ->object[0]->isInstanceOf('\RREST\Error');

        //ipv6
        $schema = json_decode('{
          "$schema": "http://json-schema.org/schema",
          "type": "object",
          "properties": {
            "artist": { "type": "string", "format": "ipv6" }
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
            ->object[0]->isInstanceOf('\RREST\Error');

        //date-time
        $schema = json_decode('{
          "$schema": "http://json-schema.org/schema",
          "type": "object",
          "properties": {
            "artist": { "type": "string", "format": "date-time" }
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
            ->object[0]->isInstanceOf('\RREST\Error');

        //hostname
        $schema = json_decode('{
          "$schema": "http://json-schema.org/schema",
          "type": "object",
          "properties": {
            "artist": { "type": "string", "format": "hostname" }
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
            ->object[0]->isInstanceOf('\RREST\Error');

    }
}