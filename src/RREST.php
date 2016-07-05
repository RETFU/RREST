<?php

namespace RREST;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use JsonSchema\Validator;
use Negotiation\Negotiator;
use Negotiation\Exception\InvalidArgument;
use RREST\APISpec\APISpecInterface;
use RREST\Router\RouterInterface;
use RREST\Exception\InvalidParameterException;
use RREST\Exception\InvalidPayloadBodyException;
use RREST\Exception\InvalidJSONException;

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
     * @var boolean
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
    public function setAsserResponse($assert) {
        $this->assertResponse = $assert;
    }

    /**
     * @return bool
     */
    public function getAsserResponse() {
        return $this->assertResponse;
    }

    /**
     * @return Route
     */
    public function addRoute()
    {
        $method = $this->apiSpec->getRouteMethod();
        $controllerClassName = $this->getRouteControllerClassName(
            $this->apiSpec->getRessourcePath()
        );

        $this->assertControllerClassName($controllerClassName);
        $this->assertActionMethodName($controllerClassName, $method);

        $availableAcceptContentTypes = $this->apiSpec->getResponsePayloadBodyContentTypes();
        $accept = $this->getBestHeaderAccept($this->getHeader('Accept'), $availableAcceptContentTypes);
        $this->assertHTTPHeaderAccept($availableAcceptContentTypes, $accept);

        $contentType = $this->getHeader('Content-Type');
        $availableContentTypes = $this->apiSpec->getRequestPayloadBodyContentTypes();
        $this->assertHTTPHeaderContentType($availableContentTypes, $contentType);

        $protocol = $this->getProtocol();
        $availableProtocols = $this->apiSpec->getProtocols();
        $this->assertHTTPProtocol($availableProtocols, $protocol);

        $requestSchema = $this->apiSpec->getRequestPayloadBodySchema($contentType);
        $payloadBodyValue = $this->router->getPayloadBodyValue();
        $statusCodeSucess = $this->getStatusCodeSuccess();
        $format = $this->getFormat($accept, self::$supportedMimeTypes);
        $mimeType = $this->getMimeType($format, self::$supportedMimeTypes);
        $routPaths = $this->getRoutePaths($this->apiSpec->getRoutePath());

        $responseSchema = $this->apiSpec->getResponsePayloadBodySchema($statusCodeSucess, $accept);
        $response = $this->getResponse($this->router, $statusCodeSucess, $format, $mimeType);
        if( $this->getAsserResponse() ) {
            $response->setSchema($responseSchema);
        }

        foreach ($routPaths as $routPath) {
            $this->router->addRoute(
                $routPath,
                $method,
                $this->getControllerNamespaceClass($controllerClassName),
                $this->getActionMethodName($method),
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
        //find a 20x code
        $statusCodes20x = array_filter($statusCodes, function ($value) {
            return preg_match('/20\d?/', $value);
        });
        if (count($statusCodes20x) === 1) {
            return (int) array_pop($statusCodes20x);
        } else {
            throw new \RuntimeException('You can\'t define multiple 20x for one resource path!');
        }
    }

    /**
     * @param string $availableHTTPProtocols
     * @param string $currentHTTPProtocol
     *
     * @throw AccessDeniedHttpException
     */
    protected function assertHTTPProtocol($availableHTTPProtocols, $currentHTTPProtocol)
    {
        $availableHTTPProtocols = array_map('strtoupper', $availableHTTPProtocols);
        $currentHTTPProtocol = strtoupper($currentHTTPProtocol);
        if (in_array($currentHTTPProtocol, $availableHTTPProtocols) === false) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @param string $availableContentTypes
     * @param string $contentType
     *
     * @throw UnsupportedMediaTypeHttpException
     */
    protected function assertHTTPHeaderContentType($availableContentTypes, $contentType)
    {
        $availableContentTypes = array_map('strtolower', $availableContentTypes);
        $contentType = strtolower($contentType);
        if (
            empty($availableContentTypes) === false &&
            in_array($contentType, $availableContentTypes) === false
        ) {
            throw new UnsupportedMediaTypeHttpException();
        }
    }

    /**
     * @param string[]    $availableContentTypes
     * @param string|bool $acceptContentType
     *
     * @throw UnsupportedMediaTypeHttpException
     */
    protected function assertHTTPHeaderAccept(array $availableContentTypes, $acceptContentType)
    {
        if (empty($acceptContentType)) {
            throw new NotAcceptableHttpException();
        }
        if (empty($availableContentTypes)) {
            throw new \RuntimeException('No content type defined for this response');
        }
        $availableContentTypes = array_map('strtolower', $availableContentTypes);
        $acceptContentType = strtolower($acceptContentType);
        if (in_array($acceptContentType, $availableContentTypes) === false) {
            throw new NotAcceptableHttpException();
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
     * @throw RREST\Exception\InvalidPayloadBodyException
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
     * @throws \RREST\Exception\InvalidPayloadBodyException
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
        $thowInvalidXMLException('RREST\Exception\InvalidPayloadBodyException');

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
     * @throws \RREST\Exception\InvalidPayloadBodyException
     */
    protected function assertHTTPPayloadBodyJSON($value, $schema)
    {
        $assertInvalidJSONException = function () {
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidJSONException([new Error(
                    ucfirst(json_last_error_msg()),
                    'invalid-payloadbody-json'
                )]);
            }
        };

        //validate JSON format
        $valueJSON = json_decode($value);
        $assertInvalidJSONException();
        $schemaJSON = json_decode($schema);
        $assertInvalidJSONException();

        //validate JsonSchema
        $jsonValidator = new Validator();
        $jsonValidator->check($valueJSON, $schemaJSON);
        if ($jsonValidator->isValid() === false) {
            $invalidBodyError = [];
            foreach ($jsonValidator->getErrors() as $jsonError) {
                $invalidBodyError[] = new Error(
                    ucfirst(trim(strtolower(
                        $jsonError['property'].' property: '.$jsonError['message']
                    ))),
                    'invalid-payloadbody-json'
                );
            }
            if (empty($invalidBodyError) == false) {
                throw new InvalidPayloadBodyException($invalidBodyError);
            }
        }

        $this->hintedPayloadBody = $valueJSON;
    }

    protected function hintHTTPPayloadBody($hintedPayloadBody)
    {
        $this->router->setPayloadBodyValue($hintedPayloadBody);
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
     * @param $action
     *
     * @return string
     * @throw RuntimeException
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
        // remove URI parameters like controller/90/subcontroller/50
        $controllerClassName = preg_replace('/\{[^}]+\}/', '', $routePath);
        $controllerClassName = trim(str_replace('//', '/', $controllerClassName));
        $controllerClassName = trim($controllerClassName, '/');
        $controllerClassName = preg_replace('/[^a-z\/]/', '', $controllerClassName);

        $chunks = explode('/', $controllerClassName);
        $controllerClassName = ucwords($controllerClassName);

        if (count($chunks) > 1) {
            $chunks = array_map('ucwords', $chunks);
            $controllerClassName = implode('\\', $chunks);
        }

        return $controllerClassName;
    }

    /**
     * @param string $action
     *
     * @return string
     */
    public function getActionMethodName($action)
    {
        return strtolower($action).'Action';
    }

    /**
     * @param string $controllerClassName
     *
     * @return string
     */
    public function getControllerNamespaceClass($controllerClassName)
    {
        return $this->controllerNamespace.'\\'.$controllerClassName;
    }

    /**
     * Return the protocol (http or https) used.
     *
     * @return string
     */
    public function getProtocol()
    {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        } elseif (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ||
            !empty($_SERVER['HTTP_X_FORWARDED_SSL']) &&
            $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
        ) {
            $isSecure = true;
        }

        return $isSecure ? 'HTTPS' : 'HTTP';
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
     * Find the best Accept header depending of priorities.
     *
     * @param string $acceptRaw
     * @param array  $priorities
     *
     * @return string|null
     */
    private function getBestHeaderAccept($acceptRaw, array $priorities)
    {
        if (empty($acceptRaw)) {
            return;
        }

        try {
            $negotiaor = new Negotiator();
            $accept = $negotiaor->getBest($acceptRaw, $priorities);
        } catch (InvalidArgument $e) {
            $accept = null;
        }

        if (is_null($accept)) {
            return;
        }

        return $accept->getValue();
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getHeader($name)
    {
        $name = strtolower($name);
        if (empty($this->headers)) {
            $this->headers = array_change_key_case(getallheaders(), CASE_LOWER);
            if (empty($this->headers)) {
                $this->headers = array_change_key_case($_SERVER, CASE_LOWER);
            }
        }
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }

        return;
    }
}
