<?php

namespace RREST\tests\units;

require_once __DIR__.'/boostrap.php';

use atoum;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use RREST\Router\Silex;
use RREST\APISpec\RAML;

class RREST extends atoum
{
    /**
     * @var \Raml\Parser
     */
    public $apiDefinition;

    public function beforeTestMethod($method)
    {
        if (is_null($this->apiDefinition)) {
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
     * @param Application $app
     *
     * @return Silex
     */
    private function getSilexRouter(Application $app)
    {
        return new Silex($app);
    }

    /**
     * @param string $method
     * @param string $routePath
     *
     * @return RAML
     */
    public function getRAMLAPISpec($apiDefinition, $method, $routePath)
    {
        $apiSpec = new RAML($apiDefinition, $method, $routePath);

        return $apiSpec;
    }

    public function testAddRoute()
    {
        date_default_timezone_set('UTC');
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $router = $this->getSilexRouter($this->getSilexApplication());

        //good
        $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/json';
        $this
            ->given($this->newTestedInstance($apiSpec, $router, 'RREST\tests\units'))
            ->object($this->testedInstance->addRoute())
            ->isInstanceOf('RRest\Route')
        ;

        //missing controller
        $this
            ->exception(
                function () use ($router) {
                    $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/json';
                    $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('RREST\tests\Songs not found')
        ;

        //missing controller method
        $this
            ->exception(
                function () use ($router) {
                    $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/json';
                    $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'DELETE', '/v1/songs/98');
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('\RuntimeException')
            ->message->contains('Songs::deleteAction method not found')
        ;

        //bad accept
        $this
            ->exception(
                function () use ($apiSpec, $router) {
                    $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/jxson';
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException')
        ;

        //bad content-type
        $this
            ->exception(
                function () use ($router) {
                    $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/98');
                    $_SERVER['Accept'] = 'application/json';
                    $_SERVER['Content-Type'] = 'application/jsonx';
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
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
                function () use ($apiSpec, $router) {
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                }
            )
            ->isInstanceOf('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException')
        ;
        unset($_SERVER['HTTPS']);

        //bad parameters
        date_default_timezone_set('UTC'); // for datetime validation
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $app = $this->getSilexApplication();
        $router = $this->getSilexRouter($app);
        $this
            ->exception(
                function () use ($app, $apiSpec, $router) {
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                    $request = Request::create('/v1/songs/98', 'GET', ['id' => '25'], [], [], []);
                    $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidParameterException')
            ->array($this->exception->getErrors())
            ->hasSize(1)
            ->object($this->exception->getErrors()[0])
            ->isInstanceOf('RREST\Error')
            ->string($this->exception->getErrors()[0]->message)
            ->isEqualTo('id maximum size is 10')
        ;

        //parameters hinted
        $this
            ->given($this->newTestedInstance($apiSpec, $router, 'RREST\tests\units'))
            ->and(
                $this->testedInstance->addRoute(),
                $request = Request::create('/v1/songs/98', 'GET', ['id' => '10'], [], [], []),
                $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false),
                $id = $router->getParameterValue('id')
            )
            ->integer($id)
            ->isEqualTo(10)
        ;

        //bad json payload body
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/90');
        $app = $this->getSilexApplication();
        $router = $this->getSilexRouter($app);
        $this
            ->exception(
                function () use ($app, $apiSpec, $router) {
                    $router->setPayloadBodyValue('bad json'); //because we are in a CLI context and can't set php://input
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                    $request = Request::create('/v1/songs/90', 'PUT', [], [], [], [], 'bad json');
                    $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidJSONException')
            ->array($this->exception->getErrors())
            ->hasSize(1)
            ->object($this->exception->getErrors()[0])
            ->isInstanceOf('RREST\Error')
            ->string($this->exception->getErrors()[0]->message)
        ;

        //bad json schema payload body
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/90');
        $app = $this->getSilexApplication();
        $router = $this->getSilexRouter($app);
        $this
            ->exception(
                function () use ($app, $apiSpec, $router) {
                    $router->setPayloadBodyValue('{}'); //because we are in a CLI context and can't set php://input
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                    $request = Request::create('/v1/songs/90', 'PUT', [], [], [], [], '{}');
                    $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidRequestPayloadBodyException')
            ->array($this->exception->getErrors())
            ->hasSize(1)
            ->object($this->exception->getErrors()[0])
            ->isInstanceOf('RREST\Error')
            ->string($this->exception->getErrors()[0]->code)
            ->isEqualTo('43')
        ;

        //json payload body hinted
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/90');
        $app = $this->getSilexApplication();
        $router = $this->getSilexRouter($app);
        $router->setPayloadBodyValue('{"title":"title","artist":"artist"}'); //because we are in a CLI context and can't set php://input
        $this
            ->given($this->newTestedInstance($apiSpec, $router, 'RREST\tests\units'))
            ->and(
                $this->testedInstance->addRoute(),
                $request = Request::create('/v1/songs/90', 'PUT', [], [], [], [], '{"title":"title","artist":"artist"}'),
                $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false),
                $song = $router->getPayloadBodyValue()
            )
            ->object($song)
            ->isInstanceOf('\stdClass');

        //bad XML payload body
        $_SERVER['Accept'] = $_SERVER['Content-Type'] = 'application/xml';
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/90');
        $app = $this->getSilexApplication();
        $router = $this->getSilexRouter($app);
        $this
            ->exception(
                function () use ($app, $apiSpec, $router) {
                    $router->setPayloadBodyValue('bad xml'); //because we are in a CLI context and can't set php://input
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                    $request = Request::create('/v1/songs/90', 'PUT', [], [], [], [], 'bad xml');
                    $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidXMLException')
            ->array($this->exception->getErrors())
            ->hasSize(1)
            ->object($this->exception->getErrors()[0])
            ->isInstanceOf('RREST\Error')
            ->string($this->exception->getErrors()[0]->message)
        ;

        //bad XML schema payload body
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/90');
        $app = $this->getSilexApplication();
        $router = $this->getSilexRouter($app);
        $this
            ->exception(
                function () use ($app, $apiSpec, $router) {
                    $router->setPayloadBodyValue('<song></song>'); //because we are in a CLI context and can't set php://input
                    $this->newTestedInstance($apiSpec, $router, 'RREST\tests\units');
                    $this->testedInstance->addRoute();
                    $request = Request::create('/v1/songs/90', 'PUT', [], [], [], [], '<song></song>');
                    $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
                }
            )
            ->isInstanceOf('RREST\Exception\InvalidRequestPayloadBodyException')
            ->array($this->exception->getErrors())
            ->hasSize(1)
            ->object($this->exception->getErrors()[0])
            ->isInstanceOf('RREST\Error')
            ->string($this->exception->getErrors()[0]->message)
            ->isNotEmpty()
        ;

        // xml payload body hinted
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'PUT', '/v1/songs/90');
        $app = $this->getSilexApplication();
        $router = $this->getSilexRouter($app);
        $router->setPayloadBodyValue('<song><title>qsd</title><artist>qsd</artist></song>'); //because we are in a CLI context and can't set php://input
        $this
            ->given($this->newTestedInstance($apiSpec, $router, 'RREST\tests\units'))
            ->and(
                $this->testedInstance->addRoute(),
                $request = Request::create('/v1/songs/90', 'PUT', [], [], [], [], '<song><title>qsd</title><artist>qsd</artist></song>'),
                $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false),
                $song = $router->getPayloadBodyValue()
            )
            ->object($song)
            ->isInstanceOf('\stdClass');
    }

    public function testGetActionMethodName()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $router = $this->getSilexRouter($this->getSilexApplication());

        $this
            ->given($this->newTestedInstance($apiSpec, $router))
            ->string($this->testedInstance->getActionMethodName('get'))
            ->isEqualTo('getAction')
        ;
    }

    public function testGetControllerNamespaceClass()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $router = $this->getSilexRouter($this->getSilexApplication());

        $this
            ->given($this->newTestedInstance($apiSpec, $router))
            ->string($this->testedInstance->getControllerNamespaceClass('Songs'))
            ->isEqualTo('Controllers\\Songs')
        ;

        $this
            ->given($this->newTestedInstance($apiSpec, $router, 'Path\\To\\Controllers'))
            ->string($this->testedInstance->getControllerNamespaceClass('Songs'))
            ->isEqualTo('Path\\To\\Controllers\\Songs')
        ;
    }

    public function testGetProtocol()
    {
        $apiSpec = $this->getRAMLAPISpec($this->apiDefinition, 'GET', '/v1/songs/98');
        $router = $this->getSilexRouter($this->getSilexApplication());

        $this
            ->given($this->newTestedInstance($apiSpec, $router))
            ->string($this->testedInstance->getProtocol())
            ->isEqualTo('HTTP')
        ;

        $_SERVER['HTTPS'] = true;
        $this
            ->given($this->newTestedInstance($apiSpec, $router))
            ->string($this->testedInstance->getProtocol())
            ->isEqualTo('HTTPS')
        ;

        $_SERVER['HTTPS'] = 'on';
        $this
            ->given($this->newTestedInstance($apiSpec, $router))
            ->string($this->testedInstance->getProtocol())
            ->isEqualTo('HTTPS')
        ;

        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_X_FORWARDED_SSL'] = 'on';
        $this
            ->given($this->newTestedInstance($apiSpec, $router))
            ->string($this->testedInstance->getProtocol())
            ->isEqualTo('HTTPS')
        ;
    }
}

class Songs
{
    public function getAction(Application $app, Request $request, \RREST\Response $response, $slotId = null)
    {
        return $response->getRouterResponse();
    }

    public function putAction(Application $app, Request $request, \RREST\Response $response, $slotId = null)
    {
        return $response->getRouterResponse();
    }
}
