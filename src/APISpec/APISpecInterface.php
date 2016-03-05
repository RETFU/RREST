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
     * Return all auths type supported by the current route
     *
     * @return string[]
     */
    public function getAuthTypes();

    /**
     * Return the ressource matched in the APISpec.
     *
     * @example: /item/id
     *
     * @return string
     */
    public function getRessourcePath();

    /**
     * @return integer[]
     */
    public function getStatusCodes();

    /**
     * @return string[]
     */
    public function getProtocols();

    /**
     * @return RREST\Parameter[]
     */
    public function getParameters();

    /**
     * @return string[]|boolean
     */
    public function getRequestPayloadBodyContentTypes();

    /**
     * @param  string $contentType
     * @return string|boolean
     */
    public function getRequestPayloadBodySchema($contentType);

    /**
     * @return string[]|boolean
     */
    public function getResponsePayloadBodyContentTypes();

    //TODO: add response validation against schema
    //public function getResponsePayloadBodySchema($contentType);
}
