<?php

namespace RREST\Provider;

use RREST\Response;

interface ProviderInterface
{
    /**
     * Adding a new provider route.
     *
     * @param string  $routePath
     * @param string  $method
     * @param string  $controllerClassName
     * @param string  $actionMethodName
     * @param Closure $assertRequestFunction
     */
    public function addRoute($routePath, $method, $controllerClassName, $actionMethodName, Response $response, \Closure $assertRequestFunction);

    /**
     * Apply CORS (cross-origin resource sharing) to answer to an OPTION request.
     *
     * @param string $origin
     * @param string $methods
     * @param string $headers
     *
     * @return bool
     */
    public function applyCORS($origin = '*', $methods = 'GET,POST,PUT,DELETE,OPTIONS', $headers = '');

    /**
     * @return string
     */
    public function getHTTPProtocol();

    /**
     * Return the parameter typed or raw value.
     *
     * @param string $key
     * @param string $type
     *
     * @return mixed
     */
    public function getHTTPParameterValue($key, $type);

    /**
     * Set the parameter value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setHTTPParameterValue($key, $value);

    /**
     * Return the payload body content.
     *
     * @return string
     */
    public function getHTTPPayloadBodyValue();

    /**
     * Set the payload body
     *
     * @param string $key
     */
    public function setHTTPPayloadBodyValue($payloadBodyJSON);

    /**
     * The response provide by the provider
     *
     * @param  int $statusCode
     * @param  string $contentType
     *
     * @return mixed
     */
    public function getHTTPResponse($statusCode, $contentType);
}
