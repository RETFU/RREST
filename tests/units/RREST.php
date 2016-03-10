<?php
namespace RREST\tests\units;

require_once __DIR__ . '/boostrap.php';

use atoum;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
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
    private function getSilexApplication()
    {
        return new Application();
    }

    /**
     * @param  Application $app
     * @return Silex
     */
    private function getSilexProvider(Application $app)
    {
        return new Silex($app);
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

    public function testAddRoute()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $provider = $this->getSilexProvider( $this->getSilexApplication() );

        //good
        $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/json';
        $this
            ->given($this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units'))
            ->object($this->testedInstance->addRoute())
            ->isInstanceOf('RRest\Route')
        ;

        //missing controller
        $this
            ->exception(
                function() use ($provider) {
                    $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/json';
                    $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
                    $this->newTestedInstance($apiSpec, $provider, 'RREST\tests');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('RREST\tests\Songs not found')
        ;

        //missing controller method
        $this
            ->exception(
                function() use ($provider) {
                    $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/json';
                    $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'DELETE', '/v1/songs/98');
                    $this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('Songs::deleteAction method not found')
        ;

        //bad accept
        $this
            ->exception(
                function() use ($apiSpec, $provider) {
                    $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/jxson';
                    $this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException')
        ;

        //bad content-type
        $this
            ->exception(
                function() use ($provider) {
                    $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/98');
                    $_SERVER['Accept'] = 'application/json';
                    $_SERVER['Content-Type'] = 'application/jsonx';
                    $this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException')
        ;

        //bad protocol
        $_SERVER['HTTPS'] = true;
        $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/json';
        $this
            ->exception(
                function() use ($apiSpec, $provider) {
                    $this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException')
        ;
        unset($_SERVER['HTTPS']);

        //bad parameters
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $app =  $this->getSilexApplication();
        $provider = $this->getSilexProvider($app);
        $this
            ->exception(
                function() use ($app, $apiSpec, $provider) {
                    $this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                    $request = Request::create('/v1/songs/98','GET',['id'=>'25'],[],[],[]);
                    $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
        ;
        //TODO test error array?

        //parameters hinted
        $this
            ->given($this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units'))
            ->and(
                $this->testedInstance->addRoute(),
                $request = Request::create('/v1/songs/98','GET',['id'=>'10'],[],[],[]),
                $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false),
                $id = $provider->getParameterValue('id')
            )
            ->integer($id)
            ->isEqualTo(10)
        ;

        //bad json payload body
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/90');
        $app =  $this->getSilexApplication();
        $provider = $this->getSilexProvider($app);
        $this
            ->exception(
                function() use ($app, $apiSpec, $provider) {
                    $this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                    $request = Request::create('/v1/songs/90','PUT',[],[],[],[],'bad json');
                    $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidPayloadBodyException')
        ;
        //TODO test error array?
        //bad json schema payload body
        $this
            ->exception(
                function() use ($app, $apiSpec, $provider) {
                    $this->newTestedInstance($apiSpec, $provider, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                    $request = Request::create('/v1/songs/90','PUT',[],[],[],[],'{"title":"title"}');
                    $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidPayloadBodyException')
        ;
        //TODO test error array?
    }

    public function testGetActionMethodName()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $provider = $this->getSilexProvider( $this->getSilexApplication() );

        $this
            ->given($this->newTestedInstance($apiSpec, $provider))
            ->string($this->testedInstance->getActionMethodName('get'))
            ->isEqualTo('getAction')
        ;
    }

    public function testGetControllerNamespaceClass()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $provider = $this->getSilexProvider( $this->getSilexApplication() );

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
        $provider = $this->getSilexProvider( $this->getSilexApplication() );

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

class Songs
{
    public function getAction(Application $app, Request $request, \RREST\Response $response, $slotId=null)
    {
        return $response->getProviderResponse();
    }

    public function putAction(Application $app, Request $request, \RREST\Response $response, $slotId)
    {
        return $response->getProviderResponse();
    }
}
