<?php

namespace RREST\tests\units\Validator;

require_once __DIR__ . '/../boostrap.php';

use atoum;

class JsonValidator extends atoum
{
    public function testFails()
    {
        $this
            ->given($this->newTestedInstance('{}', '{}  '))
            ->boolean($this->testedInstance->fails())
            ->isFalse();

        $schema = file_get_contents(__DIR__ . '/../../fixture/song.json');
        $this
            ->given($this->newTestedInstance('{}', $schema))
            ->boolean($this->testedInstance->fails())
            ->isTrue();


        $this
            ->exception(
                function () {
                    $this->newTestedInstance('}', '}')->fails();
                }
            )
            ->isInstanceOf('\RREST\Exception\InvalidJSONException');
    }
}