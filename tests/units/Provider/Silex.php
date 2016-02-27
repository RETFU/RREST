<?php
namespace RREST\tests\units\Provider;

require_once __DIR__ . '/../boostrap.php';

use atoum;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use RREST\Response;

class Silex extends atoum
{
    public function testAddRoute()
    {
        //just check if adding a route with the Silex Provider
        //work when a request happen on the route GET /
        $app = new Application();
        $this->newTestedInstance($app);
        $this
            ->given( $this->testedInstance )
            ->and(
                $this->testedInstance->addRoute(
                    '/','GET','RREST\tests\units\Provider\Controller','getAction',
                    new Response($this->testedInstance,'json',201),
                    function(){}
                ),
                $request = Request::create('/','GET',[],[],[],[],'YYY'),
                $response = $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false)
            )
            ->object($response)
            ->isInstanceOf('Symfony\Component\HttpFoundation\Response')
        ;
    }

    public function testGetResponse()
    {
        $app = new Application();
        $this
            ->given( $this->newTestedInstance($app) )
            ->object($this->testedInstance->getResponse('XXX'))
            ->isInstanceOf('Symfony\Component\HttpFoundation\Response');
        ;
    }

    public function testGetPayloadBodyValue()
    {
        $app = new Application();
        $this->newTestedInstance($app);
        $this
            ->given( $this->testedInstance )
            ->and(
                $this->testedInstance->setPayloadBodyValue('XXX')
            )
            ->string($this->testedInstance->getPayloadBodyValue())
            ->isEqualTo('XXX');
        ;

        $app = new Application();
        $this->newTestedInstance($app);
        $this
            ->given( $this->testedInstance )
            ->and(
                $this->testedInstance->addRoute(
                    '/','GET','RREST\tests\units\Provider\Controller','getAction',
                    new Response($this->testedInstance,'json',201),
                    function(){}
                ),
                $request = Request::create('/','GET',[],[],[],[],'YYY'),
                $response = $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false)
            )
            ->string($this->testedInstance->getPayloadBodyValue())
            ->isEqualTo('YYY');
        ;

    }

    public function testGetParameterValue()
    {
        $app = new Application();
        $this->newTestedInstance($app);
        $this
            ->given( $this->testedInstance )
            ->and(
                $this->testedInstance->addRoute(
                    '/','GET','RREST\tests\units\Provider\Controller','getAction',
                    new Response($this->testedInstance,'json',201),
                    function(){}
                ),
                $parameters = ['parameter'=>'5'],
                $request = Request::create('/','GET',$parameters,[],[],[],'YYY'),
                $response = $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false)
            )
            ->string($this->testedInstance->getParameterValue('parameter','string'))
            ->isEqualTo('5')
        ;
    }

    public function testSetParameterValue()
    {
        $app = new Application();
        $this->newTestedInstance($app);
        $this
            ->given( $this->testedInstance )
            ->and(
                $this->testedInstance->addRoute(
                    '/','GET','RREST\tests\units\Provider\Controller','getAction',
                    new Response($this->testedInstance,'json',201),
                    function(){}
                ),
                $parameters = ['parameter'=>'5'],
                $request = Request::create('/','GET',$parameters,[],[],[],'YYY'),
                $response = $app->handle($request, HttpKernelInterface::MASTER_REQUEST, false),
                $this->testedInstance->setParameterValue('parameter',5)
            )
            ->integer($this->testedInstance->getParameterValue('parameter','string'))
            ->isEqualTo(5)
        ;
    }
}

//probably not the best practice
class Controller
{
    public function getAction(Application $app, Request $request, Response $response)
    {
        return $response->getProviderResponse();
    }
}
