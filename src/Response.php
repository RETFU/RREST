<?php

namespace RREST;

use League\JsonGuard;
use RREST\Validator\JsonValidator;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use RREST\Router\RouterInterface;
use RREST\Exception\InvalidResponsePayloadBodyException;
use RREST\Exception\InvalidJSONException;

class Response
{
    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var string
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $schema;

    /**
     * @var string[]
     */
    protected $supportedFormat = ['json', 'xml', 'csv', 'xlsx'];

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * The URL of a resource, useful when POST a new one.
     *
     * @var string
     */
    protected $headerLocation;

    /**
     * @var string
     */
    protected $headerContentType;

    public function __construct(RouterInterface $router, $format, $statusCode)
    {
        $this->setFormat($format);
        $this->setRouter($router);
        $this->setStatusCode($statusCode);
    }

    /**
     * @param string
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string
     */
    public function setFormat($format)
    {
        if (in_array($format, $this->supportedFormat) === false) {
            throw new \RuntimeException(
                'format not supported, only are '.implode(', ', $this->supportedFormat).' availables'
            );
        }
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getConfiguredHeaderstatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param string
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->headerLocation;
    }

    /**
     * @param mixed
     */
    public function setLocation($headerLocation)
    {
        $this->headerLocation = $headerLocation;
    }

    /**
     * @return mixed
     */
    public function getContentType()
    {
        return $this->headerContentType;
    }

    /**
     * @param mixed
     */
    public function setContentType($headerContentType)
    {
        $this->headerContentType = $headerContentType;
    }

    /**
     * @param string
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * All headers configured, index by header name.
     *
     * @return string[]
     */
    public function getConfiguredHeaders()
    {
        $headers = [];
        $contentType = $this->getContentType();
        if (empty($contentType) === false) {
            $headers['Content-Type'] = $contentType;
        }
        $location = $this->getLocation();
        if (empty($location) === false) {
            $headers['Location'] = $location;
        }

        return $headers;
    }

    /**
     * @param mixed $content
     *
     * @return bool
     */
    public function setContent($content)
    {
        $this->content = $content;

        $this->assertReponseSchema(
            $this->getFormat(),
            $this->getSchema(),
            \json_encode($content)
        );
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return RouterInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Get a router configured response with:
     * - content serialize
     * - success status code
     * - header Content-Type
     * - header Location.
     *
     * @param bool $autoSerializeContent
     *
     * @return mixed
     */
    public function getRouterResponse($autoSerializeContent = true)
    {
        $content = $this->getContent();
        if ($autoSerializeContent) {
            $content = $this->serialize($content, $this->getFormat());
        }

        return $this->router->getResponse(
            $content, $this->getConfiguredHeaderstatusCode(), $this->getConfiguredHeaders(), $this->file
        );
    }

    /**
     * @param mixed  $data
     * @param string $format
     *
     * @return string
     */
    public function serialize($data, $format)
    {
        if ($format === 'json') {
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($format === 'xml') {
            $serializer = new Serializer([
                    new ObjectNormalizer(),
                ], [
                    'xml' => new XmlEncoder(),
                ]
            );
            //fix stdClass not serialize by default
            $data = json_decode(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), true);

            return $serializer->serialize($data, $format);
        } elseif ($format === 'csv' || $format === 'xlsx') {
            if (!is_string($data)) {
                throw new \RuntimeException(
                    'auto serialization for '.strtoupper($format).' format is not supported'
                );
            }

            return $data;
        } else {
            throw new \RuntimeException(
                'format not supported, only are '.implode(', ', $this->supportedFormat).' availables'
            );
        }
    }

    /**
     * @param string $format
     * @param string $schema
     * @param string $value
     *
     * @throws InvalidResponsePayloadBodyException
     * @throws InvalidJSONException
     * @throws InvalidXMLException
     */
    public function assertReponseSchema($format, $schema, $value)
    {
        if (empty($schema)) {
            return;
        }

        switch (true) {
            case strpos($format, 'json') !== false:
                $this->assertResponseJSON($value, $schema);
                break;
            case strpos($format, 'xml') !== false:
                $this->assertResponseXML($value, $schema);
                break;
            case strpos($format, 'csv') !== false:
                // no validation for CSV
                break;
            default:
                throw new \RuntimeException(
                    'format not supported, only are '.implode(', ', $this->supportedFormat).' availables'
                );
                break;
        }
    }

    /**
     * @param string $value
     * @param string $schema
     *
     * @throws \RREST\Exception\InvalidXMLException
     * @throws \RREST\Exception\InvalidResponsePayloadBodyException
     */
    public function assertResponseXML($value, $schema)
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
                        'invalid-response-xml'
                    );
                }
                if (empty($invalidBodyError) == false) {
                    throw new $exceptionClassName(
                        $invalidBodyError,
                        "Invalid XML Response body"
                    );
                }
            }
        };

        //validate XML
        $originalErrorLevel = libxml_use_internal_errors(true);
        $valueDOM = new \DOMDocument();
        $valueDOM->loadXML($value);
        $thowInvalidXMLException('RREST\Exception\InvalidXMLException');

        //validate XMLSchema
        $valueDOM->schemaValidateSource($schema);
        $thowInvalidXMLException('RREST\Exception\InvalidResponsePayloadBodyException');

        libxml_use_internal_errors($originalErrorLevel);
    }

    /**
     * @param string $value
     * @param string $schema
     *
     * @throws \RREST\Exception\InvalidJSONException
     * @throws \RREST\Exception\InvalidResponsePayloadBodyException
     */
    public function assertResponseJSON($value, $schema)
    {
        $validator = new JsonValidator($value, $schema);
        if($validator->fails()) {
            throw new InvalidResponsePayloadBodyException(
                $validator->getErrors(),
                "Invalid JSON response body"
            );
        }
    }
}
