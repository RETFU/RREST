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
     * Return the protocol (http or https) used
     *
     * @return string
     */
    public function getProtocol();

    /**
     * Return the Content-type header value
     *
     * @return string
     */
    public function getContentType();

    /**
     * Return the Accept header value
     *
     * @return string
     */
    public function getAccept();

    /**
     * Return the parameter typed or raw value if can't be hinted
     *
     * @param string $key
     * @param string $type
     *
     * @return mixed
     */
    public function getParameterValue($key, $type);

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
     * @param string $key
     */
    public function setPayloadBodyValue($payloadBodyJSON);

    /**
     * The provider response
     *
     * @param  int $statusCode
     * @param  string $contentType
     * @param  string[] $headers
     *
     * @return mixed
     */
    public function getResponse($content = '', $statusCode = 200, $headers = array());
}
