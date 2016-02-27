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

    public function testSerialize()
    {
        $app = new Application();
        $provider = new Silex($app);
        $this->newTestedInstance($provider,'json',200);
        $data = new \stdClass;
        $data->name = 'diego';
        $data->age = 3;
        $data->moods = new \stdClass;
        $data->moods = ['angry','cool','happy'];

        $this
            ->given( $this->testedInstance )
            ->string($this->testedInstance->serialize($data,'json'))
            ->isEqualTo('{"name":"diego","age":3,"moods":["angry","cool","happy"]}')
        ;

        $this
            ->given( $this->testedInstance )
            ->string($this->testedInstance->serialize($data,'xml'))
            ->isEqualTo("<?xml version=\"1.0\"?>\n<response><name>diego</name><age>3</age><moods>angry</moods><moods>cool</moods><moods>happy</moods></response>\n")
        ;

        $this
            ->exception(
                function() use ($data) {
                    $this->testedInstance->serialize($data,'xxx');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('format not supported')
        ;
    }

    public function testGetProviderResponse()
    {
        $app = new Application();
        $provider = new Silex($app);
        $this->newTestedInstance($provider,'json',201);
        $data = new \stdClass;
        $data->name = 'diego';
        $data->age = 3;
        $data->moods = new \stdClass;
        $data->moods = ['angry','cool','happy'];

        $this
            ->given( $this->testedInstance )
            ->object($this->testedInstance->getProviderResponse())
            ->isInstanceOf('Symfony\Component\HttpFoundation\Response');
        ;

        $this->testedInstance->setContent($data);
        $this
            ->given( $this->testedInstance )
            ->string($this->testedInstance->getProviderResponse()->getContent())
            ->isEqualTo('{"name":"diego","age":3,"moods":["angry","cool","happy"]}')
        ;

        $this->testedInstance->setContent('ABC');
        $this
            ->given( $this->testedInstance )
            ->string($this->testedInstance->getProviderResponse(false)->getContent())
            ->isEqualTo('ABC')
        ;

        $this
            ->given( $this->testedInstance )
            ->integer($this->testedInstance->getProviderResponse()->getStatusCode())
            ->isEqualTo(201)
        ;

        $this->testedInstance->setContentType('application/xml');
        $this->testedInstance->setLocation('https://api.domain.com/items/uuid');

        $this
            ->given( $this->testedInstance )
            ->string($this->testedInstance->getProviderResponse()->headers->get('Content-Type'))
            ->isEqualTo('application/xml')
            ->string($this->testedInstance->getProviderResponse()->headers->get('Location'))
            ->isEqualTo('https://api.domain.com/items/uuid')
        ;
    }
}
