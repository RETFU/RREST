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
use RREST\Parameter;

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
     * @param strings       $currentURLPath (PHP_URL_PATH)
     */
    public function __construct(ApiDefinition $apiDefinition, $currentURLPath)
    {
        $this->apiDefinition = $apiDefinition;
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
     * @inheritdoc
     */
    public function getSupportedHTTPProtocols()
    {
        return $this->apiDefinition->getProtocols();
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
    public function getParameters()
    {
        $parameters = [];
        $namedParameters = array_merge(
            $this->method->getQueryParameters(),
            $this->resource->getUriParameters(),
            $this->resource->getBaseUriParameters(),
            $this->method->getBaseUriParameters()
        );
        foreach ($namedParameters as $nameParameter) {
            $parameter = new Parameter(
                $nameParameter->getKey(),
                $nameParameter->getType(),
                $nameParameter->isRequired()
            );
            $parameter->setDateFormat('D, d M Y H:i:s T'); //RFC2616 from RAML spec
            $parameter->setEnum( (array) $nameParameter->getEnum() );
            $parameter->setValidationPattern( $nameParameter->getValidationPattern() );
            switch ($nameParameter->getType()) {
                case NamedParameter::TYPE_STRING:
                    $parameter->setMinimum( $nameParameter->getMinLength() );
                    $parameter->setMaximum( $nameParameter->getMaxLength() );
                    break;
                case NamedParameter::TYPE_INTEGER:
                case NamedParameter::TYPE_NUMBER:
                    $parameter->setMinimum( $nameParameter->getMinimum() );
                    $parameter->setMaximum( $nameParameter->getMaximum() );
                    break;
                default:
                    break;
            }

            $parameters[] = $parameter;
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentTypes()
    {
        $contentTypes = [];
        foreach ($this->method->getBodies() as $body) {
            $contentTypes[] = $body->getMediaType();
        }
        return $contentTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayloadBodySchema($contentType)
    {
        if( empty( $this->method->getBodies() ) === false ) {
            return (string) $this->method->getBodyByType($contentType)->getSchema();
        }
        return false;
    }

    /**
     * @param Raml\Resource $resource
     *
     * @throw Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     *
     * @return Raml\Method
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
     *
     * @throw Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \Raml\Resource|false
     */
    protected function getResourceFromPath($path)
    {
        try {
            $resource = $this->apiDefinition->getResourceByUri($path);
        } catch (ResourceNotFoundException $e) {
                //Try with a trailing slash to accept /resource/ and /ressource
            try {
                $resource = $this->apiDefinition->getResourceByUri($path.'/');
            } catch (ResourceNotFoundException $e) {
                throw new NotFoundHttpException($e->getMessage());
            }
        }

        return $resource;
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
