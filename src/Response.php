<?php
namespace RREST;

use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

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
     * The callback to set the serialized content into
     * this provider response
     *
     * @var \Closure
     */
    protected $contentCallBack;

    /**
     * @param mixed $providerResponse
     * @param string $format
     * @param \Closure $contentCallBack
     */
    public function __construct($providerResponse, $format, $contentCallBack)
    {
        $this->setProviderResponse($providerResponse);
        $this->setFormat($format);
        $this->setContentCallBack($contentCallBack);
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
     * @param Closure $contentCallBack
     */
    public function setContentCallBack(\Closure $contentCallBack)
    {
        $this->contentCallBack = $contentCallBack;
    }

    /**
     * @return Closure $contentCallBack
     */
    public function getContentCallBack()
    {
        return $this->contentCallBack;
    }

    /**
     * @param mixed $content
     *
     * @return boolean
     */
    public function setContent($content)
    {
        $this->content = $content;
        $serializer = new Serializer([
                new ObjectNormalizer()
            ],[
                'xml' => new XmlEncoder(),
                'json' => new JsonEncoder(),
            ]
        );
        $content = $serializer->serialize($this->getContent(), $this->getFormat());
        return call_user_func_array(
            $this->contentCallBack, [$this,$content]
        );
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
}
