<?php

namespace RREST\tests\units;

require_once __DIR__.'/boostrap.php';

use atoum;
use RREST\Router\Silex;
use Silex\Application;

class Response extends atoum
{
    /**
     * @var Silex
     */
    public $router;

    /**
     * @var \stdClass
     */
    public $data;

    public function beforeTestMethod($method)
    {
        if (is_null($this->router)) {
            $app = new Application();
            $this->router = new Silex($app);

            $this->data = new \stdClass();
            $this->data->name = 'diego';
            $this->data->age = 3;
            $this->data->moods = new \stdClass();
            $this->data->moods = ['angry', 'cool', 'happy'];
        }
    }

    public function testSetFormat()
    {
        $this
            ->exception(
                function () {
                    $this->newTestedInstance($this->router, 'json', 200);
                    $this->testedInstance->setFormat('xxx');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('format not supported')
        ;
    }

    public function testGetConfiguredHeaders()
    {
        $this->newTestedInstance($this->router, 'json', 200);
        $this->testedInstance->setContentType('application/xml');
        $this->testedInstance->setLocation('https://api.domain.com/items/uuid');

        $this
            ->given($this->testedInstance)
            ->array($this->testedInstance->getConfiguredHeaders())
            ->hasKey('Content-Type')
            ->hasKey('Location')
            ->hasSize(2)
            ->strictlyContainsValues(array(
                'application/xml',
                'https://api.domain.com/items/uuid',
            ))
        ;
    }

    public function testSerialize()
    {
        $this->newTestedInstance($this->router, 'json', 200);
        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->serialize($this->data, 'json'))
            ->isEqualTo('{"name":"diego","age":3,"moods":["angry","cool","happy"]}')
        ;

        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->serialize($this->data, 'xml'))
            ->isEqualTo("<?xml version=\"1.0\"?>\n<response><name>diego</name><age>3</age><moods>angry</moods><moods>cool</moods><moods>happy</moods></response>\n")
        ;

        $this
            ->exception(
                function () {
                    $this->testedInstance->serialize($this->data, 'xxx');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('format not supported')
        ;
    }

    public function testNoCsvSerialize()
    {
        $this->newTestedInstance($this->router, 'csv', 200);

        $this
            ->given($this->testedInstance)
            ->exception(
                function () {
                    $this->testedInstance->serialize([], 'csv');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('auto serialization for CSV format is not supported');
    }

    public function testSerializeForCsv()
    {
        $this->newTestedInstance($this->router, 'csv', 200);

        $csv = "The Black Keys;El Camino\nFoo Fighters;Sonic Highways";

        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->serialize($csv, 'csv'))
            ->isEqualTo($csv);
        ;
    }

    public function testNoXlsxSerialize()
    {
        $this->newTestedInstance($this->router, 'xlsx', 200);

        $this
            ->given($this->testedInstance)
            ->exception(
                function () {
                    $this->testedInstance->serialize([], 'xlsx');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('auto serialization for XLSX format is not supported');
    }

    public function testSerializeForXlsx()
    {
        $this->newTestedInstance($this->router, 'xlsx', 200);

        $xlsx = "Placeholder for binary content";

        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->serialize($xlsx, 'xlsx'))
            ->isEqualTo($xlsx);
        ;
    }

    public function testNoZipSerialize()
    {
        $this->newTestedInstance($this->router, 'zip', 200);

        $this
            ->given($this->testedInstance)
            ->exception(
                function () {
                    $this->testedInstance->serialize([], 'zip');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('auto serialization for ZIP format is not supported');
    }

    public function testSerializeForZip()
    {
        $this->newTestedInstance($this->router, 'zip', 200);

        $zip = "Placeholder for binary content";

        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->serialize($zip, 'zip'))
            ->isEqualTo($zip);
        ;
    }

    public function testAssertReponseSchema()
    {
        $this->newTestedInstance($this->router, 'json', 200);
        $this
            ->exception(
                function () {
                    $this->testedInstance->assertReponseSchema('xxx', 'ddd', 'ddd');
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('format not supported')
        ;

        $this
            ->exception(
                function () {
                    $this->testedInstance->assertReponseSchema('json', 'ddd', 'ddd');
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidJSONException')
        ;

        $this
            ->exception(
                function () {
                    $schema = file_get_contents(__DIR__.'/../fixture/song.json');
                    $value = '{"title":"title","artist":4}';
                    $this->testedInstance->assertReponseSchema('json', $schema, $value);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidResponsePayloadBodyException')
        ;

        $this
            ->exception(
                function () {
                    $this->testedInstance->assertReponseSchema('xml', 'ddd', 'ddd');
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidXMLException')
        ;

        $this
            ->exception(
                function () {
                    $schema = file_get_contents(__DIR__.'/../fixture/song.xml');
                    $value = '<song><title>qsd</title></song>';
                    $this->testedInstance->assertReponseSchema('xml', $schema, $value);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidResponsePayloadBodyException')
        ;
    }

    public function testGetRouterResponse()
    {
        $this->newTestedInstance($this->router, 'json', 201);

        $this
            ->given($this->testedInstance)
            ->object($this->testedInstance->getRouterResponse())
            ->isInstanceOf('Symfony\Component\HttpFoundation\Response');

        $this->testedInstance->setContent($this->data);
        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->getRouterResponse()->getContent())
            ->isEqualTo('{"name":"diego","age":3,"moods":["angry","cool","happy"]}')
        ;

        $this->testedInstance->setContent('ABC');
        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->getRouterResponse(false)->getContent())
            ->isEqualTo('ABC')
        ;

        $this
            ->given($this->testedInstance)
            ->integer($this->testedInstance->getRouterResponse()->getStatusCode())
            ->isEqualTo(201)
        ;

        $this->testedInstance->setContentType('application/xml');
        $this->testedInstance->setLocation('https://api.domain.com/items/uuid');

        $this
            ->given($this->testedInstance)
            ->string($this->testedInstance->getRouterResponse()->headers->get('Content-Type'))
            ->isEqualTo('application/xml')
            ->string($this->testedInstance->getRouterResponse()->headers->get('Location'))
            ->isEqualTo('https://api.domain.com/items/uuid')
        ;
    }

    public function testGetRouterResponseWithFile()
    {
        $this->newTestedInstance($this->router, 'json', 201);

        $this
            ->given($this->testedInstance)
            ->and(
                $this->testedInstance->setFile(__DIR__.'/../fixture/song.xml')
            )
            ->object($this->testedInstance->getRouterResponse())
            ->isInstanceOf('Symfony\Component\HttpFoundation\BinaryFileResponse');
    }
}
