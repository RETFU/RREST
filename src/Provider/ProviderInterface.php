<?php

namespace RREST\Provider;

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
    public function addRoute($routePath, $method, $controllerClassName, $actionMethodName, \Closure $assertRequestFunction);

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
     * Return the parameter typed or raw value.
     *
     * FIXME $type depend of what CastToType can
     * handle, not really good
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
     * TODO automatic decode JSON for example
     *
     * @return string
     */
    public function getHTTPPayloadBodyValue();
}
