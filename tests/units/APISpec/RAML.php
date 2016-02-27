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
}
