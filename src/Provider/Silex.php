<?php

namespace RREST\Provider;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

/**
 * Silex provider.
 */
class Silex implements ProviderInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function addRoute($routePath, $method, $controllerClassName, $actionMethodName, \Closure $assertRequestFunction)
    {
        $controller = $this->app->match(
            $routePath,
            $controllerClassName.'::'.$actionMethodName
        )
        ->method(strtoupper($method));

        // In Silex, at this point, $this->request->attribute is not set.
        // So we can't validate baseUriParameter like itemId -> /item/{itemId}/
        // That's why we must wait app routing & use a closure to keep the Logic
        // in the RREST class.
        // $this->request = Request::createFromGlobals();
        $controller->before(function (Request $request) use ($assertRequestFunction) {
            $this->request = $request;
            $assertRequestFunction();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function applyCORS($origin = '*', $methods = 'GET,POST,PUT,DELETE,OPTIONS', $headers = '')
    {
        $this->app->before(function (Request $request) use ($origin, $methods, $headers) {
            if ($request->getMethod() === 'OPTIONS') {
                return $this->app->json(null, 200, [
                    'Access-Control-Allow-Origin' => $origin,
                    'Access-Control-Allow-Methods' => $methods,
                    'Access-Control-Allow-Headers' => $headers,
                ]);
            }
        }, Application::EARLY_EVENT);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getHTTPProtocol()
    {
        return $this->request->getScheme();
    }

    /**
     * {@inheritdoc}
     */
    public function getHTTPParameterValue($key, $type)
    {
        $parameterBags = ['query', 'attributes', 'request'];
        // Search in all Silex Request parameters
        foreach ($parameterBags as $parameterBag) {
            $requestParam = $this->request->{$parameterBag};
            if ($requestParam->has($key)) {
                return $requestParam->get($key);
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function setHTTPParameterValue($key, $value)
    {
        $parameterBags = ['query', 'attributes', 'request'];
        // Search in Silex Request parameters
        foreach ($parameterBags as $parameterBag) {
            $requestParam = $this->request->{$parameterBag};
            if ($requestParam->has($key)) {
                $requestParam->set($key, $value);
                //stop searching parameter when finding one
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHTTPPayloadBodyValue()
    {
        return $this->request->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        return $this->request->headers->get('Content-Type');
    }
}
