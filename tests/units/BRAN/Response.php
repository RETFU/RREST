<?php
namespace RREST\tests\units;

require_once __DIR__ . '/../boostrap.php';

use atoum;
use RREST\Provider\Silex;
use Silex\Application;

class Response extends atoum
{
    public function testSetFormat()
    {
        $this
            ->exception(
                function() {
                    $app = new Application();
                    $provider = new Silex($app);
                    $this->newTestedInstance($provider,'json',200);
                    $this->testedInstance->setFormat('xxx');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('format not supported')
        ;
    }

    public function testGetConfiguredHeaders()
    {
        $app = new Application();
        $provider = new Silex($app);
        $this->newTestedInstance($provider,'json',200);
        $this->testedInstance->setContentType('application/xml');
        $this->testedInstance->setLocation('https://api.domain.com/items/uuid');

        $this
            ->given( $this->testedInstance )
            ->array($this->testedInstance->getConfiguredHeaders())
            ->hasKey('Content-Type')
            ->hasKey('Location')
            ->hasSize(2)
            ->strictlyContainsValues(array(
                'application/xml',
                'https://api.domain.com/items/uuid'
            ))
        ;
    }
}
