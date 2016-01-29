<?php

namespace RREST\APISpec;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Raml\ApiDefinition;
use Raml\Resource;
use Raml\Method;
use Raml\Exception\BadParameter\ResourceNotFoundException;
use Raml\Exception\ValidationException;
use Raml\Exception\InvalidSchemaException;
use Raml\NamedParameter;
use RREST\Exception\InvalidBodyException;
use RREST\Exception\InvalidParameterException;
use RREST\Error;

/**
 * RAML APISpec.
 *
 * @see http://raml.org
 */
class RAML implements APISpecInterface
{
    /**
     * @var ApiDefinition
     */
    private $apiDefinition;

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var Method
     */
    private $method;

    /**
     * @param ApiDefinition $apiDefinition
     */
    public function __construct(ApiDefinition $apiDefinition)
    {
        $this->apiDefinition = $apiDefinition;
        $currentURLPath = $this->extractCurrentURLPath();
        $resourcePath = $this->extractRessourcePathFromURL(
            $currentURLPath, $this->apiDefinition->getVersion()
        );
        $this->resource = $this->getResourceFromPath($resourcePath);
        $this->method = $this->getMethodFromResource($this->resource);
    }

    /**
     * @return string
     */
    public function getRoutePath()
    {
        return '/'.$this->apiDefinition->getVersion().$this->resource->getUri();
    }

    /**
     * @return string
     */
    public function getRessourcePath()
    {
        return $this->resource->getUri();
    }

    /**
     * @return string
     */
    public function getRouteMethod()
    {
        return $this->method->getType();
    }

    /**
     * @return string
     */
    // public function getRouteHeader($headerName)
    // {
    //     return $this->method->getHeaders();
    // }

    /**
     * {@inheritdoc}
     */
    public function getParameterValueForAssertion($type, $value, $castValue)
    {
        if ($type === NamedParameter::TYPE_DATE) {
            return $value;
        }

        return $castValue;
    }

    /**
     * {@inheritdoc}
     */
    public function assertHTTPParameters(\Closure $getTypedParameterValue)
    {
        $invalidParametersError = [];
        $namedParameters = array_merge(
            $this->method->getQueryParameters(),
            $this->resource->getUriParameters(),
            $this->resource->getBaseUriParameters(),
            $this->method->getBaseUriParameters()
        );
        foreach ($namedParameters as $nameParameter) {
            try {
                $value = $getTypedParameterValue(
                    $nameParameter->getKey(),
                    $nameParameter->getType()
                );
                $nameParameter->validate($value);
            } catch (ValidationException $e) {
                $invalidParametersError[] = new Error(
                    $e->getMessage(),
                    $e->getCode()
                );
            }
        }

        if (empty($invalidParametersError) == false) {
            throw new InvalidParameterException($invalidParametersError);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertHTTPPayloadBody($bodyValue)
    {
        $invalidBodyError = [];
        $bodies = $this->method->getBodies();
        foreach ($bodies as $body) {
            try {
                $body->getSchema()->validate($bodyValue);
            } catch (InvalidSchemaException $e) {
                foreach ($e->getErrors() as $isError) {
                    $error = new Error();
                    $error->message = ucfirst(
                        trim(
                            strtolower(
                                $isError['property'].' property: '.$isError['message']
                            )
                        )
                    );
                    $error->code = $e->getCode();
                    $invalidBodyError[] = $error;
                }
                throw new InvalidBodyException($invalidBodyError);
            }
        }

        if (empty($invalidBodyError) == false) {
            throw new InvalidBodyException($invalidBodyError);
        }
    }

    /**
     * @param resource $resource
     * @throw MethodNotAllowedHttpException
     *
     * @return Method
     */
    protected function getMethodFromResource(Resource $resource)
    {
        try {
            $method = $resource->getMethod($_SERVER['REQUEST_METHOD']);
        } catch (\Exception $e) {
            $methods = [];
            foreach ($resource->getMethods() as $method) {
                $methods[] = $method->getType();
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
                throw new MethodNotAllowedHttpException($methods, $e->getMessage());
            }
        }

        return $method;
    }

    /**
     * @param string $path
     * @throw NotFoundHttpException
     *
     * @return resource|false
     */
    protected function getResourceFromPath($path)
    {
        try {
            $resource = $this->apiDefinition->getResourceByUri($path);
        } catch (ResourceNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        return $resource;
    }

    /**
     * @return string
     */
    protected function extractCurrentURLPath()
    {
        return parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        );
    }

    /**
     * Extract ressource path from an URL.
     * http://localhost:8080/v1/users/50/ -> /users/50/.
     *
     * @param string $split
     *
     * @return string
     */
    private function extractRessourcePathFromURL($url, $split)
    {
        $resourcePath = explode($split, $url)[1];

        return $resourcePath;
    }
}
