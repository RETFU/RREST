<?php
namespace RREST\tests\units\APISpec;

require_once __DIR__ . '/../boostrap.php';

use atoum;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use RREST\Response;

class RAML extends atoum
{
    public function testRouteNotFound()
    {
        $this
            ->exception(
                function() {
                    $apiDefinition = (new \Raml\Parser())->parse(__DIR__.'/../../fixture/song.raml');
                    $this->newTestedInstance($apiDefinition, 'GET', '/v1/songsX');
                }
            )
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\NotFoundHttpException')
        ;
    }

    public function testUseAuthentificationMechanism()
    {
        $apiDefinition = (new \Raml\Parser())->parse(__DIR__.'/../../fixture/song.raml');

        $this->newTestedInstance($apiDefinition, 'GET', '/v1/songs');
        $this
            ->given( $this->testedInstance )
            ->boolean($this->testedInstance->useAuthentificationMechanism())
            ->isFalse()
        ;

        $this->newTestedInstance($apiDefinition, 'GET', '/v1/songs/85');
        $this
            ->given( $this->testedInstance )
            ->boolean($this->testedInstance->useAuthentificationMechanism())
            ->isTrue()
        ;
    }

    public function testGetStatusCodes()
    {
        $apiDefinition = (new \Raml\Parser())->parse(__DIR__.'/../../fixture/song.raml');

        $this->newTestedInstance($apiDefinition, 'GET', '/v1/songs');
        $this
            ->given( $this->testedInstance )
            ->array($this->testedInstance->getStatusCodes())
            ->hasSize(1)
            ->strictlyContainsValues(array(200))
        ;
    }

    public function testGetRequestPayloadBodyContentTypes()
    {
        $apiDefinition = (new \Raml\Parser())->parse(__DIR__.'/../../fixture/song.raml');

        $this->newTestedInstance($apiDefinition, 'PUT', '/v1/songs/90');
        $this
            ->given( $this->testedInstance )
            ->array($this->testedInstance->getRequestPayloadBodyContentTypes())
            ->hasSize(2)
            ->strictlyContainsValues(array('application/json','application/xml'))
        ;
    }

    public function testGetResponsePayloadBodyContentTypes()
    {
        $apiDefinition = (new \Raml\Parser())->parse(__DIR__.'/../../fixture/song.raml');

        $this->newTestedInstance($apiDefinition, 'GET', '/v1/songs');
        $this
            ->given( $this->testedInstance )
            ->array($this->testedInstance->getResponsePayloadBodyContentTypes())
            ->hasSize(1)
            ->strictlyContainsValues(array('application/json'))
        ;

        $this->newTestedInstance($apiDefinition, 'DELETE', '/v1/songs/89');
        $this
            ->given( $this->testedInstance )
            ->array($this->testedInstance->getResponsePayloadBodyContentTypes())
            ->hasSize(0)
        ;
    }

    public function testGetRequestPayloadBodySchema()
    {
        $apiDefinition = (new \Raml\Parser())->parse(__DIR__.'/../../fixture/song.raml');

        $this->newTestedInstance($apiDefinition, 'PUT', '/v1/songs/90');
        $this
            ->given( $this->testedInstance )
            ->string($this->testedInstance->getRequestPayloadBodySchema('application/json'))
            ->contains('$schema')
        ;
        $this
            ->given( $this->testedInstance )
            ->string($this->testedInstance->getRequestPayloadBodySchema('application/xml'))
            ->isEmpty()
        ;
    }
}
