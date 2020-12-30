<?php

namespace RREST\tests\units;

require_once __DIR__.'/boostrap.php';

use atoum;

class Controller extends atoum
{
    public function testGetFullyQualifiedName()
    {
        $this->newTestedInstance('RREST\tests\units', '/fakestatus', 'get');
        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->getFullyQualifiedName())
            ->isEqualTo('RREST\tests\units\Fakestatus')
        ;
    }

    public function testGetFullyQualifiedNameWithMoreComplexPath()
    {
        $this->newTestedInstance('RREST\tests\units', '/fakestatus/{id}/test', 'get');
        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->getFullyQualifiedName())
            ->isEqualTo('RREST\tests\units\Fakestatus\Test')
        ;
    }

    public function testGetFullyQualifiedNameNotExist()
    {
        $this->newTestedInstance('y', '/x', 'get');
        $this
            ->exception(
                function () {
                    $this->newTestedInstance('Y', '/x', 'get');
                    $this->testedInstance->getFullyQualifiedName();
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('Y\X not found')
        ;
    }

    public function testGetActionMethodName()
    {
        $this->newTestedInstance('RREST\tests\units', '/fakestatus/{id}/test', 'get');
        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->getActionMethodName('Get'))
            ->isEqualTo('getAction')
        ;
    }

    public function testGetActionMethodNameNotExist()
    {
        $this
            ->exception(
                function () {
                    $this->newTestedInstance('RREST\tests\units', '/fakestatus/{id}/test', 'POST');
                    $this->testedInstance->getActionMethodName();
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('RREST\tests\units\Fakestatus\Test::postAction method not found')
        ;
    }
}


//fixture
class Fakestatus
{
    public function getAction()
    {
    }
}
namespace RREST\tests\units\Fakestatus;

class Test
{
    public function getAction()
    {
    }
}
