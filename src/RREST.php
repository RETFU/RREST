<?php

namespace RREST;

use RREST\APISpec\APISpecInterface;
use RREST\Exception\InvalidParameterException;
use RREST\Exception\InvalidRequestPayloadBodyException;
use RREST\Router\RouterInterface;
use RREST\Validator\AcceptValidator;
use RREST\Validator\ContentTypeValidator;
use RREST\Validator\JsonValidator;
use RREST\Validator\ProtocolValidator;
use RREST\Util\HTTP;

/**
 * ApiSpec + Router = RREST.
 */
class RREST
{
    /**
     * @var array[]
     */
    public static $supportedMimeTypes = [
        'json' => ['application/json', 'application/x-json'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'csv' => ['text/csv', 'application/csv'],
    ];

    /**
     * @var APISpecInterface
     */
    protected $apiSpec;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $controllerNamespace;

    /**
     * @var array
     */
    protected $hintedHTTPParameters;

    /**
     * @var array|\stdClass
     */
    protected $hintedPayloadBody;

    /**
     * @var bool
     */
    protected $assertResponse = true;

    /**
     * @var string[]
     */
    private $headers;

    /**
     * @param APISpecInterface $apiSpec
     * @param RouterInterface  $router
     * @param string           $controllerNamespace
     */
    public function __construct(APISpecInterface $apiSpec, RouterInterface $router, $controllerNamespace = 'Controllers')
    {
        $this->apiSpec = $apiSpec;
        $this->router = $router;
        $this->controllerNamespace = $controllerNamespace;
        $this->hintedHTTPParameters = [];
    }

    /**
     * Define if the response will be validate against the APISpec
     * schema or not. You can make it to false in production to have better
     * performance on large response, like list of object.
     *
     * @param bool $assert
     */
    public function setAssertResponse($assert)
    {
        $this->assertResponse = $assert;
    }

    /**
     * @return bool
     */
    public function getAssertResponse()
    {
        return $this->assertResponse;
    }

    /**
     * @return Route
     */
    public function addRoute()
    {
        $method = $this->apiSpec->getRouteMethod();
        $controller = new Controller(
            $this->controllerNamespace,
            $this->apiSpec->getRessourcePath(),
            $method
        );

        //accept
        $acceptValidator = new AcceptValidator(
            HTTP::getHeader('Accept'),
            $this->apiSpec->getResponsePayloadBodyContentTypes()
        );
        if($acceptValidator->fails()) {
            throw $acceptValidator->getException();
        }
        $accept = $acceptValidator->getBestAccept();

        //content-type
        $contentType = HTTP::getHeader('Content-Type');
        $contentTypeValidator = new ContentTypeValidator(
            $contentType,
            $this->apiSpec->getRequestPayloadBodyContentTypes()
        );
        if($contentTypeValidator->fails()) {
            throw $contentTypeValidator->getException();
        }

        //protocol
        $protocolValidator = new ProtocolValidator(
            HTTP::getProtocol(),
            $this->apiSpec->getProtocols()
        );
        if($protocolValidator->fails()) {
            throw $protocolValidator->getException();
        }

        $requestSchema = $this->apiSpec->getRequestPayloadBodySchema($contentType);
        $payloadBodyValue = $this->router->getPayloadBodyValue();
        $statusCodeSucess = $this->getStatusCodeSuccess();
        $format = $this->getFormat($accept, self::$supportedMimeTypes);
        $mimeType = $this->getMimeType($format, self::$supportedMimeTypes);
        $routPaths = $this->getRoutePaths($this->apiSpec->getRoutePath());

        $responseSchema = $this->apiSpec->getResponsePayloadBodySchema($statusCodeSucess, $accept);
        $response = $this->getResponse($this->router, $statusCodeSucess, $format, $mimeType);
        if ($this->getAssertResponse()) {
            $response->setSchema($responseSchema);
        }

        foreach ($routPaths as $routPath) {
            $this->router->addRoute(
                $routPath,
                $method,
                $controller->getFullyQualifiedName(),
                $controller->getActionMethodName(),
                $response,
                function () use ($contentType, $requestSchema, $payloadBodyValue) {
                    $this->assertHTTPParameters();
                    $this->assertHTTPPayloadBody($contentType, $requestSchema, $payloadBodyValue);
                    $this->hintHTTPParameterValue($this->hintedHTTPParameters);
                    $this->hintHTTPPayloadBody($this->hintedPayloadBody);
                }
            );
        }

        $route = new Route($routPath, $method, $this->apiSpec->getAuthTypes());

        return $route;
    }

    /**
     * Return all routes path for the the APISpec.
     * This help to no worry about calling the API
     * with or without a trailing slash.
     *
     * @param string $apiSpecRoutePath
     *
     * @return string[]
     */
    protected function getRoutePaths($apiSpecRoutePath)
    {
        $routePaths = [];
        $routePaths[] = $apiSpecRoutePath;
        if (substr($apiSpecRoutePath, -1) === '/') {
            $routePaths[] = substr($apiSpecRoutePath, 0, -1);
        } else {
            $routePaths[] = $apiSpecRoutePath.'/';
        }

        return $routePaths;
    }

    /**
     * @param RouterInterface $router
     * @param string          $statusCodeSucess
     * @param string          $format
     * @param string          $mimeType
     *
     * @return Response
     */
    protected function getResponse(RouterInterface $router, $statusCodeSucess, $format, $mimeType)
    {
        if ($format === false) {
            throw new \RuntimeException(
                'Can\'t find a supported format for this Accept header.
                RRest only support json & xml.'
            );
        }
        $response = new Response($router, $format, $statusCodeSucess);
        $response->setContentType($mimeType);

        return $response;
    }

    /**
     * Find the sucess status code to apply at the end of the request.
     *
     * @return int
     */
    protected function getStatusCodeSuccess()
    {
        $statusCodes = $this->apiSpec->getStatusCodes();
        $successfulStatusCodes = array_filter($statusCodes, function ($value) {
            return preg_match('/^[23]0\d?$/', $value);
        });
        if (count($successfulStatusCodes) === 1) {
            return (int) array_pop($successfulStatusCodes);
        } else {
            throw new \RuntimeException('You can\'t define multiple 20x for one resource path!');
        }
    }

    /**
     * @throw InvalidParameterException
     */
    protected function assertHTTPParameters()
    {
        $invalidParametersError = [];
        $parameters = $this->apiSpec->getParameters();
        foreach ($parameters as $parameter) {
            $value = $this->router->getParameterValue($parameter->getName());
            try {
                $castValue = $this->cast($value, $parameter->getType());
            } catch (\Exception $e) {
                throw new InvalidParameterException([
                    new Error(
                        $e->getMessage(),
                        $e->getCode()
                    ),
                ]);
            }
            try {
                $parameter->assertValue($castValue);
                $this->hintedHTTPParameters[$parameter->getName()] = $castValue;
            } catch (InvalidParameterException $e) {
                $invalidParametersError = array_merge(
                    $e->getErrors(),
                    $invalidParametersError
                );
            }
        }

        if (empty($invalidParametersError) == false) {
            throw new InvalidParameterException($invalidParametersError);
        }
    }

    protected function hintHTTPParameterValue($hintedHTTPParameters)
    {
        foreach ($hintedHTTPParameters as $key => $value) {
            $this->router->setParameterValue($key, $value);
        }
    }

    /**
     * @param string $contentType
     * @param string $schema
     * @param string $value
     *
     * @throw RREST\Exception\InvalidRequestPayloadBodyException
     * @throw RREST\Exception\InvalidJSONException
     * @throw RREST\Exception\InvalidXMLException
     */
    protected function assertHTTPPayloadBody($contentType, $schema, $value)
    {
        //No payload body here, no need to assert
        if ($schema === false) {
            return;
        }

        switch (true) {
            case strpos($contentType, 'json') !== false:
                $this->assertHTTPPayloadBodyJSON($value, $schema);
                break;
            case strpos($contentType, 'xml') !== false:
                $this->assertHTTPPayloadBodyXML($value, $schema);
                break;
            default:
                break;
        }
    }

    /**
     * @param string $value
     * @param string $schema
     *
     * @throws \RREST\Exception\InvalidXMLException
     * @throws \RREST\Exception\InvalidRequestPayloadBodyException
     */
    protected function assertHTTPPayloadBodyXML($value, $schema)
    {
        $thowInvalidXMLException = function ($exceptionClassName) {
            $invalidBodyError = [];
            $libXMLErrors = libxml_get_errors();
            libxml_clear_errors();
            if (empty($libXMLErrors) === false) {
                foreach ($libXMLErrors as $libXMLError) {
                    $message = $libXMLError->message.' (line: '.$libXMLError->line.')';
                    $invalidBodyError[] = new Error(
                        $message,
                        'invalid-payloadbody-xml'
                    );
                }
                if (empty($invalidBodyError) == false) {
                    throw new $exceptionClassName($invalidBodyError);
                }
            }
        };

        //validate XML
        $originalErrorLevel = libxml_use_internal_errors(true);
        $valueDOM = new \DOMDocument();
        $valueDOM->loadXML($value);
        $thowInvalidXMLException('RREST\Exception\InvalidXMLException');

        //validate XMLSchema
        $invalidBodyError = [];
        $valueDOM->schemaValidateSource($schema);
        $thowInvalidXMLException('RREST\Exception\InvalidRequestPayloadBodyException');

        libxml_use_internal_errors($originalErrorLevel);

        //use json to convert the XML to a \stdClass object
        $valueJSON = json_decode(json_encode(simplexml_load_string($value)));

        $this->hintedPayloadBody = $valueJSON;
    }

    /**
     * @param string $value
     * @param string $schema
     *
     * @throws \RREST\Exception\InvalidJSONException
     * @throws \RREST\Exception\InvalidRequestPayloadBodyException
     */
    protected function assertHTTPPayloadBodyJSON($value, $schema)
    {
        $validator = new JsonValidator($value, $schema);
        if($validator->fails()) {
            throw new InvalidRequestPayloadBodyException(
                $validator->getErrors()
            );
        }

        $this->hintedPayloadBody = \json_decode($value);
    }

    protected function hintHTTPPayloadBody($hintedPayloadBody)
    {
        $this->router->setPayloadBodyValue($hintedPayloadBody);
    }

    /**
     * @param string   $mimeType
     * @param string[] $availableMimeTypes
     *
     * @return string|bool
     */
    private function getFormat($mimeType, $availableMimeTypes)
    {
        $format = false;
        foreach ($availableMimeTypes as $format => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                break;
            }
        }

        return $format;
    }

    /**
     * @param string   $format
     * @param string[] $availableMimeTypes
     *
     * @return string|bool
     */
    private function getMimeType($format, $availableMimeTypes)
    {
        $mimeType = false;
        if (array_key_exists($format, $availableMimeTypes)) {
            $mimeType = $availableMimeTypes[$format][0];
        }

        return $mimeType;
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    private function cast($value, $type)
    {
        $castValue = $value;
        if ($type == 'number') {
            $type = 'num';
        }

        if ($type != 'datetime') {
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
}
