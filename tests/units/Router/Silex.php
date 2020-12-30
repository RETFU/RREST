<?php

namespace RREST\tests\units\Router;

require_once __DIR__.'/../boostrap.php';

use atoum;
use RREST\Response;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Silex extends atoum
{
    /**
     * @var Application
     */
    public $app;

    public function beforeTestMethod($method)
    {
        if (is_null($this->app)) {
            $this->app = new Application();
        }
    }

    public function testAddRoute()
    {
        //just check if adding a route with the Silex Router
        //work when a request happen on the route GET /
        $this->newTestedInstance($this->app);
        $this
            ->given($this->testedInstance)
            ->and(
                $this->testedInstance->addRoute(
                    '/',
                    'GET',
                    'RREST\tests\units\Router\Controller',
                    'getAction',
                    new Response($this->testedInstance, 'json', 201),
                    function () {
                    }
                ),
                $request = Request::create('/', 'GET', [], [], [], [], 'YYY'),
                $response = $this->app->handle($request, HttpKernelInterface::MASTER_REQUEST, false)
            )
            ->object($response)
            ->isInstanceOf('Symfony\Component\HttpFoundation\Response')
        ;
    }

    public function testGetResponse()
    {
        $this->newTestedInstance($this->app);
        $this
            ->given($this->testedInstance)
            ->object($this->testedInstance->getResponse('XXX'))
            ->isInstanceOf('Symfony\Component\HttpFoundation\Response');
    }

    public function testGetResponseWithFile()
    {
        $this->newTestedInstance($this->app);
        $this
            ->given($this->testedInstance)
            ->object($this->testedInstance->getResponse('XXX', 200, [], __DIR__.'/../../fixture/song.xml'))
            ->isInstanceOf('Symfony\Component\HttpFoundation\BinaryFileResponse');
    }

    public function testGetPayloadBodyValue()
    {
        $this->newTestedInstance($this->app);
        $this
            ->given($this->testedInstance)
            ->and(
                $this->testedInstance->setPayloadBodyValue('XXX')
            )
            ->string($this->testedInstance->getPayloadBodyValue())
            ->isEqualTo('XXX');

        $this->newTestedInstance($this->app);
        $this
            ->given($this->testedInstance)
            ->and(
                $this->testedInstance->addRoute(
                    '/',
                    'GET',
                    'RREST\tests\units\Router\Controller',
                    'getAction',
                    new Response($this->testedInstance, 'json', 201),
                    function () {
                    }
                ),
                $request = Request::create('/', 'GET', [], [], [], [], 'YYY'),
                $response = $this->app->handle($request, HttpKernelInterface::MASTER_REQUEST, false)
            )
            ->string($this->testedInstance->getPayloadBodyValue())
            ->isEqualTo('YYY');
    }

    public function testGetParameterValue()
    {
        $this->newTestedInstance($this->app);
        $this
            ->given($this->testedInstance)
            ->and(
                $this->testedInstance->addRoute(
                    '/',
                    'GET',
                    'RREST\tests\units\Router\Controller',
                    'getAction',
                    new Response($this->testedInstance, 'json', 201),
                    function () {
                    }
                ),
                $parameters = ['parameter' => '5'],
                $request = Request::create('/', 'GET', $parameters, [], [], [], 'YYY'),
                $response = $this->app->handle($request, HttpKernelInterface::MASTER_REQUEST, false)
            )
            ->string($this->testedInstance->getParameterValue('parameter', 'string'))
            ->isEqualTo('5')
            ->variable($this->testedInstance->getParameterValue('notexisting', 'string'))
            ->isNull('5')
        ;
    }

    public function testSetParameterValue()
    {
        $this->newTestedInstance($this->app);
        $this
            ->given($this->testedInstance)
            ->and(
                $this->testedInstance->addRoute(
                    '/',
                    'GET',
                    'RREST\tests\units\Router\Controller',
                    'getAction',
                    new Response($this->testedInstance, 'json', 201),
                    function () {
                    }
                ),
                $parameters = ['parameter' => '5'],
                $request = Request::create('/', 'GET', $parameters, [], [], [], 'YYY'),
                $response = $this->app->handle($request, HttpKernelInterface::MASTER_REQUEST, false),
                $this->testedInstance->setParameterValue('parameter', 5)
            )
            ->integer($this->testedInstance->getParameterValue('parameter', 'string'))
            ->isEqualTo(5)
        ;
    }
}

//probably not the best practice
class Controller
{
    public function getAction(Application $app, Request $request, Response $response)
    {
        return $response->getRouterResponse();
    }
}
