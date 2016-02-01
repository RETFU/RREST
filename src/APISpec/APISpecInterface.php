<?php

namespace RREST\APISpec;

interface APISpecInterface
{
    /**
     * Return the route path matched in the APISpec.
     *
     * @example: /v1/item/id
     *
     * @return string
     */
    public function getRoutePath();

    /**
     * Return the HTTP Method matched in the APISpec.
     *
     * @return string
     */
    public function getRouteMethod();

    /**
     * Return the ressource matched in the APISpec.
     *
     * @example: /item/id
     *
     * @return string
     */
    public function getRessourcePath();

    /**
     * @return array
     */
    public function getSupportedHTTPProtocols();

    /**
     * @return Parameter
     */
    public function getParameters();

    /**
     * Validate the payload.
     * If not valid, an exception is throw.
     *
     * @param mixed $bodyValue
     * @throw InvalidParameterException
     */
    public function assertHTTPPayloadBody($bodyValue);
}
