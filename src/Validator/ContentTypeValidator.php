<?php

namespace RREST\Validator;

use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class ContentTypeValidator implements ValidatorInterface
{
    /**
     * @var bool
     */
    private $isValidated = false;

    /**
     * @var array
     */
    private $availableContentTypes;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @param string $contentType
     * @param array $availableContentTypes
     */
    public function __construct($contentType, $availableContentTypes)
    {
        $this->availableContentTypes = array_map('strtolower', $availableContentTypes);
        $this->contentType = strtolower($contentType);
    }

    /**
     * @return bool
     */
    public function fails()
    {
        return empty($this->getException()) === false;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        $this->validate();
        return $this->exception;
    }

    public function validate()
    {
        if ($this->isValidated) {
            return;
        }

        if (empty($this->availableContentTypes) === false) {
            foreach ($this->availableContentTypes as $availableContentType) {
                if (
                    (
                        strpos($this->contentType, 'multipart/form-data') === false &&
                        $availableContentType === $this->contentType
                    ) || (
                        //not comparing with strict equality for multi-part because
                        //multipart/form-data; boundary=--------------------------699519696930389418481751
                        strpos($this->contentType, 'multipart/form-data') !== false &&
                        strpos($this->contentType, $availableContentType) !== false
                    )
                ) {
                    $this->isValidated = true;
                    //find one available content type
                    return;
                }
            }
            $this->exception = new UnsupportedMediaTypeHttpException();
        }

        $this->isValidated = true;
    }
}
