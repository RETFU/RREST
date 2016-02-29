<?php
namespace RREST\tests\units;

require_once __DIR__ . '/boostrap.php';

use atoum;
use Silex\Application;
use RREST\Provider\Silex;
use RREST\APISpec\RAML;

class RREST extends atoum
{
    /**
     * @var \Raml\Parser
     */
    public $apiDefinition;

    public function beforeTestMethod($method)
    {
        if(is_null($this->apiDefinition)) {
            $this->apiDefinition = (new \Raml\Parser())->parse(__DIR__.'/../fixture/song.raml');
        }
    }

    /**
     * @return Silex
     */
    private function getSilexProvider()
    {
        $app = new Application();
        $provider = new Silex($app);
        return $provider;
    }

    /**
     * @param  string $method
     * @param  string $routePath
     * @return RAML
     */
    public function getRAMLAPISpec($apiDefinition, $method, $routePath)
    {
        $apiSpec = new RAML($apiDefinition, $method, $routePath);
        return $apiSpec;
    }

    public function testGetActionMethodName()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $provider = $this->getSilexProvider();

        $this
            ->given($this->newTestedInstance($apiSpec, $provider))
            ->string($this->testedInstance->getActionMethodName('get'))
            ->isEqualTo('getAction')
        ;
    }

    public function testGetControllerNamespaceClass()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $provider = $this->getSilexProvider();

        $this
            ->given($this->newTestedInstance($apiSpec, $provider))
            ->string($this->testedInstance->getControllerNamespaceClass('Songs'))
            ->isEqualTo('Controllers\\Songs')
        ;

        $this
            ->given($this->newTestedInstance($apiSpec, $provider, 'Path\\To\\Controllers'))
            ->string($this->testedInstance->getControllerNamespaceClass('Songs'))
            ->isEqualTo('Path\\To\\Controllers\\Songs')
        ;
    }

    public function testGetProtocol()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $provider = $this->getSilexProvider();

        $this
            ->given($this->newTestedInstance($apiSpec, $provider))
            ->string($this->testedInstance->getProtocol())
            ->isEqualTo('HTTP')
        ;

        $_SERVER['HTTPS'] = true;
        $this
            ->given($this->newTestedInstance($apiSpec, $provider))
            ->string($this->testedInstance->getProtocol())
            ->isEqualTo('HTTPS')
        ;

        $_SERVER['HTTPS'] = 'on';
        $this
            ->given($this->newTestedInstance($apiSpec, $provider))
            ->string($this->testedInstance->getProtocol())
            ->isEqualTo('HTTPS')
        ;

        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_X_FORWARDED_SSL'] = 'on';
        $this
            ->given($this->newTestedInstance($apiSpec, $provider))
            ->string($this->testedInstance->getProtocol())
            ->isEqualTo('HTTPS')
        ;
    }
}
