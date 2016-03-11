<?php

namespace RREST\Provider;

use RREST\Response;

interface ProviderInterface
{
    /**
     * Adding a new provider route.
     *
     * @param string    $routePath
     * @param string    $method
     * @param string    $controllerClassName
     * @param string    $actionMethodName
     * @param Response  $response
     * @param \Closure  $init  A callback to call when the provider is initialized
     */
    public function addRoute($routePath, $method, $controllerClassName, $actionMethodName, Response $response, \Closure $init);

    /**
     * Return the parameter typed or raw value if can't be hinted
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getParameterValue($key);

    /**
     * Set the parameter value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setParameterValue($key, $value);

    /**
     * Return the payload body content.
     *
     * @return string
     */
    public function getPayloadBodyValue();

    /**
     * Set the payload body content.
     *
     * @param $payloadBodyJSON
     */
    public function setPayloadBodyValue($payloadBodyJSON);

    /**
     * The provider response
     *
     * @param string $content
     * @param  int $statusCode
     * @param  string[] $headers
     * @return mixed
     */
    public function getResponse($content = '', $statusCode = 200, $headers = array());
}
