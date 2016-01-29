<?php

namespace RREST;

use RREST\APISpec\APISpecInterface;
use RREST\Provider\ProviderInterface;

/**
 * ApiSpec + Provider = RREST.
 */
class RREST
{
    /**
     * @var APISpecInterface
     */
    protected $apiSpec;

    /**
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * @var string
     */
    protected $controllerNamespace;

    /**
     * @var array
     */
    protected $hintedHTTPParameters;

    /**
     * @param APISpecInterface  $apiSpec
     * @param ProviderInterface $provider
     * @param string            $controllerNamespace
     */
    public function __construct(APISpecInterface $apiSpec, ProviderInterface $provider, $controllerNamespace = 'Controllers')
    {
        $this->apiSpec = $apiSpec;
        $this->provider = $provider;
        $this->controllerNamespace = $controllerNamespace;
        $this->hintedHTTPParameters = [];
    }

    public function addRoute()
    {
        $routePath = $this->apiSpec->getRoutePath();
        $method = $this->apiSpec->getRouteMethod();
        $controllerClassName = $this->getRouteControllerClassName(
            $this->apiSpec->getRessourcePath()
        );

        $this->assertControllerClassName($controllerClassName);
        $this->assertActionMethodName($controllerClassName, $method);

        $this->provider->addRoute(
            $routePath,
            $method,
            $this->getControllerNamespaceClass($controllerClassName),
            $this->getActionMethodName($method),
            function () {
                $this->assertHTTPParameters();
                $this->assertHTTPPayloadBody();
                $this->hintHTTPParameterValue();
            }
        );
    }

    /**
     * @param string $origin
     * @param string $methods
     * @param string $headers
     *
     * @return bool
     */
    public function applyCORS($origin = '*', $methods = 'GET,POST,PUT,DELETE,OPTIONS', $headers = '')
    {
        return $this->provider->applyCORS($origin, $methods, $headers);
    }

    /**
     * @throw InvalidParameterException
     * @throw InvalidBodyException
     */
    protected function assertHTTPParameters()
    {
        $this->apiSpec->assertHTTPParameters(function ($key, $type) {
            $value = $this->provider->getHTTPParameterValue($key, $type, false);
            try {
                $castValue = $this->cast($value, $type);
            } catch (\Exception $e) {
                throw new InvalidParameterException([
                    new Error(
                        $e->getMessage(),
                        $e->getCode()
                    ),
                ]);
            }
            $this->hintedHTTPParameters[$key] = $castValue;
            //Some APISpec are validating sometime a casted value, sometime the raw value
            $value = $this->apiSpec->getParameterValueForAssertion($type, $value, $castValue);

            return $value;
        });
    }

    protected function hintHTTPParameterValue()
    {
        foreach ($this->hintedHTTPParameters as $key => $value) {
            $this->provider->setHTTPParameterValue($key, $value);
        }
    }

    /**
     * @throw InvalidBodyException
     */
    protected function assertHTTPPayloadBody()
    {
        $this->apiSpec->assertHTTPPayloadBody($this->provider->getHTTPPayloadBodyValue());
    }

    /**
     * @param string $controllerClassName
     * @throw RuntimeException
     *
     * @return string
     */
    protected function assertControllerClassName($controllerClassName)
    {
        $controllerNamespaceClass = $this->getControllerNamespaceClass($controllerClassName);
        if (class_exists($controllerNamespaceClass) == false) {
            throw new \RuntimeException(
                $controllerNamespaceClass.' not found'
            );
        }
    }

    /**
     * @param string $controllerClassName
     * @throw RuntimeException
     *
     * @return string
     */
    protected function assertActionMethodName($controllerClassName, $action)
    {
        $controllerNamespaceClass = $this->getControllerNamespaceClass($controllerClassName);
        $controllerActionMethodName = $this->getActionMethodName($action);
        if (method_exists($controllerNamespaceClass, $controllerActionMethodName) == false) {
            throw new \RuntimeException(
                $controllerNamespaceClass.'::'.$controllerActionMethodName.' method not found'
            );
        }
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    protected function cast($value, $type)
    {
        $castValue = $value;
        if ($type == 'number') {
            $type = 'num';
        }

        if ($type != 'date') {
            $castValue = \CastToType::cast($value, $type, false, true);
        } else {
            //Specific case for date
            $castValue = new \DateTime($value);
        }

        //The cast not working, parameters is probably not this $type
        if (is_null($castValue)) {
            return $value;
        }

        return $castValue;
    }

    /**
     * Return the Controller class name depending of a route path
     * By convention:
     *  - /item/{itemId}/ -> Item
     *  - /item/{itemId}/comment -> Item\Comment.
     *
     * @param string $routePath
     *
     * @return string
     */
    protected function getRouteControllerClassName($routePath)
    {
        // remove URI parameters like controller/id/subcontroller/50
        $controllerClassName = preg_replace('/\{[^}]+\}/', ' ', $routePath);
        $controllerClassName = trim(str_replace('/ /', ' ', $controllerClassName));
        $controllerClassName = trim(str_replace('/', '', $controllerClassName));
        // uppercase the first character of each word
        $controllerClassName = ucwords($controllerClassName);
        // namespace
        $controllerClassName = str_replace(' ', '\\', $controllerClassName);

        return $controllerClassName;
    }

    /**
     * @param string $action
     *
     * @return string
     */
    protected function getActionMethodName($action)
    {
        return $action.'Action';
    }

    /**
     * @param string $controllerClassName
     *
     * @return string
     */
    private function getControllerNamespaceClass($controllerClassName)
    {
        return $this->controllerNamespace.'\\'.$controllerClassName;
    }
}
