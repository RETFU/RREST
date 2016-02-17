<?php

namespace RREST;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use JsonSchema\Validator;
use Negotiation\Negotiator;
use RREST\APISpec\APISpecInterface;
use RREST\Provider\ProviderInterface;
use RREST\Exception\InvalidParameterException;
use RREST\Exception\InvalidBodyException;
use RREST\Response;

/**
 * ApiSpec + Provider = RREST.
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
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * @var string
     */
    protected $controllerNamespace;

    /**
     * @var array
     */
    protected $hintedHTTPParameters;

    /**
     * @var array|stdClass
     */
    protected $hintedPayloadBody;

    /**
     * @var string[]
     */
    private $headers;

    /**
     * @param APISpecInterface  $apiSpec
     * @param ProviderInterface $provider
     * @param string            $controllerNamespace
     */
    public function __construct(APISpecInterface $apiSpec, ProviderInterface $provider, $controllerNamespace = 'Controllers')
    {
        $this->apiSpec = $apiSpec;
        $this->provider = $provider;
        $this->controllerNamespace = $controllerNamespace;
        $this->hintedHTTPParameters = [];
    }

    public function addRoute()
    {
        $method = $this->apiSpec->getRouteMethod();
        $controllerClassName = $this->getRouteControllerClassName(
            $this->apiSpec->getRessourcePath()
        );

        $this->assertControllerClassName($controllerClassName);
        $this->assertActionMethodName($controllerClassName, $method);

        $availableAcceptContentTypes = $this->apiSpec->getResponsePayloadBodyContentTypes();
        $accept = $this->getBestHeaderAccept( $this->getHeader('Accept'), $availableAcceptContentTypes );
        $this->assertHTTPHeaderAccept($availableAcceptContentTypes,$accept);

        $contentType = $this->getHeader('Content-Type');
        $availableContentTypes = $this->apiSpec->getRequestPayloadBodyContentTypes();
        $this->assertHTTPHeaderContentType($availableContentTypes,$contentType);

        $protocol = $this->getProtocol();
        $availableProtocols = $this->apiSpec->getProtocols();
        $this->assertHTTPProtocol($availableProtocols,$protocol);

        $contentTypeSchema = $this->apiSpec->getRequestPayloadBodySchema($contentType);
        $payloadBodyValue = $this->provider->getPayloadBodyValue();
        $statCodeSucess = $this->getStatusCodeSuccess();
        $format = $this->getFormat($accept,self::$supportedMimeTypes);
        $mimeType = $this->getMimeType($format,self::$supportedMimeTypes);
        $routPaths = $this->getRoutePaths($this->apiSpec->getRoutePath());


        foreach ($routPaths as $routPath) {
            $this->provider->addRoute(
                $routPath,
                $method,
                $this->getControllerNamespaceClass($controllerClassName),
                $this->getActionMethodName($method),
                $this->getResponse($this->provider,$statCodeSucess,$format,$mimeType),
                function () use ($contentType,$contentTypeSchema,$payloadBodyValue) {
                    $this->assertHTTPParameters();
                    $this->assertHTTPPayloadBody($contentType,$contentTypeSchema,$payloadBodyValue);
                    $this->hintHTTPParameterValue($this->hintedHTTPParameters);
                    $this->hintHTTPPayloadBody($this->hintedPayloadBody);
                }
            );
        }
    }

    /**
     * Return all routes path for the the APISpec.
     * This help to no worry about calling the API
     * with or without a trailing slash.
     *
     * @param  string $apiSpecRoutePath
     *
     * @return string[]
     */
    protected function getRoutePaths($apiSpecRoutePath)
    {
        $routePaths = [];
        $routePaths[] = $apiSpecRoutePath;
        if( substr($apiSpecRoutePath, -1) === '/' ) {
            $routePaths[] = substr($apiSpecRoutePath, 0, -1);
        } else {
            $routePaths[] = $apiSpecRoutePath.'/';
        }

        return $routePaths;
    }

    /**
     * @param  ProviderInterface $provider
     * @param  string            $statusCodeSucess
     * @param  string            $format
     * @param  string            $mimeType
     *
     * @return Response
     */
    protected function getResponse(ProviderInterface $provider, $statusCodeSucess, $format, $mimeType)
    {
        if($format === false) {
            throw new \RuntimeException(
                'Can\'t find a supported format for this Accept header.
                RRest only support json & xml.'
            );
        }
        $response = new Response($provider,$format,$statusCodeSucess);
        $response->setContentType($mimeType);
        return $response;
    }

    /**
     * Find the sucess status code to apply at the end of the request
     *
     * @return int
     */
    protected function getStatusCodeSuccess()
    {
        $statusCodes = $this->apiSpec->getStatusCodes();
        //find a 20x code
        $statusCodes20x = array_filter($statusCodes, function($value) {
            return preg_match('/20\d?/', $value);
        });
        if(count($statusCodes20x) === 1) {
            return (int) array_pop($statusCodes20x);
        }
        else {
            throw new \RuntimeException('You can\'t define multiple 20x for one resource path!');
        }
        //default
        return 200;
    }

    /**
     * @param  string $availableHTTPProtocols
     * @param  string $currentHTTPProtocol
     *
     * @throw AccessDeniedHttpException
     */
    protected function assertHTTPProtocol($availableHTTPProtocols, $currentHTTPProtocol)
    {
        $availableHTTPProtocols = array_map('strtoupper', $availableHTTPProtocols);
        $currentHTTPProtocol = strtoupper($currentHTTPProtocol);
        if(in_array($currentHTTPProtocol, $availableHTTPProtocols) === false) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @param  string $availableContentTypes
     * @param  string $contentType
     *
     * @throw UnsupportedMediaTypeHttpException
     */
    protected function assertHTTPHeaderContentType($availableContentTypes, $contentType)
    {
        $availableContentTypes = array_map('strtolower', $availableContentTypes);
        $contentType = strtolower($contentType);
        if(
            empty($availableContentTypes) === false &&
            in_array($contentType,$availableContentTypes) === false
        ) {
            throw new UnsupportedMediaTypeHttpException();
        }
    }

    /**
     * @param  string           $availableContentTypes
     * @param  string|boolean   $acceptContentType
     *
     * @throw UnsupportedMediaTypeHttpException
     */
    protected function assertHTTPHeaderAccept($availableContentTypes, $acceptContentType)
    {
        if(empty($acceptContentType)) {
            throw new NotAcceptableHttpException();
        }
        $availableContentTypes = array_map('strtolower', $availableContentTypes);
        if(empty($availableContentTypes)) {
            throw new \RuntimeException('No content type defined for this response');
        }
        $acceptContentType = strtolower($acceptContentType);
        if( in_array($acceptContentType,$availableContentTypes) === false ) {
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
            $value = $this->provider->getParameterValue(
                $parameter->getName(),
                $parameter->getType()
            );
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
                $parameter->assertValue($castValue, $value);
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
            $this->provider->setParameterValue($key, $value);
        }
    }

    /**
     * @param  string $contentType
     * @param  string $schema
     * @param  string $value
     *
     * @throw RREST\Exception\InvalidBodyException
     */
    protected function assertHTTPPayloadBody($contentType, $schema, $value)
    {
        //No payload body here, no need to assert
        if($schema === false) {
            return;
        }

        $value = $this->provider->getPayloadBodyValue();
        switch (true) {
            case strpos($contentType, 'json') !== false:
                $this->assertHTTPPayloadBodyJSON($value, $schema);
                break;
            case strpos($contentType, 'xml') !== false:
                $this->assertHTTPPayloadBodyXML($value, $schema);
                break;
            default:
                throw new UnsupportedMediaTypeHttpException();
                break;
        }
    }

    /**
     * @param  string $value
     * @param  string $schema
     *
     * @throws RREST\Exception\InvalidBodyException
     *
     */
    protected function assertHTTPPayloadBodyXML($value, $schema)
    {
        $thowInvalidBodyException = function() {
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
                    throw new InvalidBodyException($invalidBodyError);
                }
            }
        };

        //validate XML
        $originalErrorLevel = libxml_use_internal_errors(true);
        $valueDOM = new \DOMDocument;
        $valueDOM->loadXML($value);
        $thowInvalidBodyException();

        //validate XMLSchema
        $invalidBodyError = [];
        $valueDOM->schemaValidateSource($schema);
        $thowInvalidBodyException();

        libxml_use_internal_errors($originalErrorLevel);

        //use json to convert the XML to a \stdClass object
        $valueJSON= json_decode(json_encode(simplexml_load_string($value)));

        $this->hintedPayloadBody= $valueJSON;
    }

    /**
     * @param  string $value
     * @param  string $schema
     *
     * @throws RREST\Exception\InvalidBodyException
     *
     */
    protected function assertHTTPPayloadBodyJSON($value, $schema)
    {
        //validate JSON
        $valueJSON = json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidBodyException([new Error(
                ucfirst(json_last_error_msg()),
                'invalid-payloadbody-json'
            )]);
        }

        //validate JsonSchema
        $jsonValidator = new Validator();
        $jsonValidator->check($valueJSON, json_decode($schema));
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
                throw new InvalidBodyException($invalidBodyError);
            }
        }

        $this->hintedPayloadBody= $valueJSON;
    }

    protected function hintHTTPPayloadBody($hintedPayloadBody)
    {
        $this->provider->setPayloadBodyValue( $hintedPayloadBody );
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
     * @throw RuntimeException
     *
     * @return string
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
        $controllerClassName = trim(str_replace('//', '', $controllerClassName));
        $controllerClassName = trim($controllerClassName, '/');
        $chunks = explode('/', $controllerClassName);
        $controllerClassName = ucwords($controllerClassName);
        if(count($chunks) > 1) {
            $chunks = array_map('ucwords',$chunks);
            $controllerClassName = implode('\\', $chunks);
        }

        return $controllerClassName;
    }

    /**
     * @param string $action
     *
     * @return string
     */
    protected function getActionMethodName($action)
    {
        return $action.'Action';
    }

    /**
     * @param string $controllerClassName
     *
     * @return string
     */
    private function getControllerNamespaceClass($controllerClassName)
    {
        return $this->controllerNamespace.'\\'.$controllerClassName;
    }

    /**
     * Return the protocol (http or https) used
     *
     * @return string
     */
    public function getProtocol()
    {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        }
        elseif(
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
     * @param  string $mimeType
     * @param  string[] $availableMimeTypes
     *
     * @return string|boolean
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
     * @param  string $format
     * @param  string[] $availableMimeTypes
     *
     * @return string|boolean
     */
    private function getMimeType($format, $availableMimeTypes)
    {
        $mimeType = false;
        if( array_key_exists($format, $availableMimeTypes) ) {
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
     * Find the best Accept header depending of priorities
     *
     * @param  string  $acceptRaw
     * @param  array  $priorities
     *
     * @return string|null
     */
    private function getBestHeaderAccept($acceptRaw, array $priorities)
    {
        $negotiaor = new Negotiator();
        $accept = $negotiaor->getBest($acceptRaw, $priorities);
        if(is_null($accept)) {
            return null;
        }
        return $accept->getValue();
    }

    /**
     * @param  string $name
     *
     * @return string
     */
    private function getHeader($name)
    {
        if( empty($this->headers) ) {
            $this->headers = apache_request_headers();
        }
        if( isset($this->headers[$name]) ) {
            return $this->headers[$name];
        }
        return null;
    }
}
