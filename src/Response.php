<?php
namespace RREST;

use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use RREST\Provider\ProviderInterface;

class Response extends HttpFoundationResponse
{
    /**
     * The provider response
     *
     * @var mixed
     */
    protected $providerResponse;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var string[]
     */
    protected $supportedFormat = ['json','xml'];

    /**
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * The URL of a resource, useful when POST a new one
     *
     * @var string
     */
    protected $resourceLocation;

    /**
     * @param mixed $providerResponse
     * @param string $format
     * @param ProviderInterface provider
     */
    public function __construct($providerResponse, $format, ProviderInterface $provider)
    {
        $this->setProviderResponse($providerResponse);
        $this->setFormat($format);
        $this->setProvider($provider);
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
        if(in_array($format, $this->supportedFormat) === false) {
            throw new \RuntimeException(
                'format not supported, only are '.implode(', ', $this->supportedFormat).' availables'
            );
        }
        $this->format = $format;
    }

    /**
     * @return mixed
     */
    public function getProviderResponse()
    {
        return $this->providerResponse;
    }

    /**
     * @param mixed
     */
    public function setProviderResponse($providerResponse)
    {
        $this->providerResponse = $providerResponse;
    }

    /**
     * @return mixed
     */
    public function getResourceLocation()
    {
        return $this->resourceLocation;
    }

    /**
     * @param mixed
     */
    public function setResourceLocation($resourceLocation)
    {
        $this->resourceLocation = $resourceLocation;

    }

    /**
     * @param ProviderInterface $provider
     */
    public function setProvider(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return ProviderInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param mixed $content
     *
     * @return boolean
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function build()
    {
        $serializer = new Serializer([
                new ObjectNormalizer()
            ],[
                'xml' => new XmlEncoder(),
                'json' => new JsonEncoder(),
            ]
        );
        $content = $serializer->serialize($this->getContent(), $this->getFormat());
        $this->provider->configureResponse($this, $content);
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
}
