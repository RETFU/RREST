<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use League\JsonGuard\ValidationError;
use RREST\Error;

class Converter implements ConverterInterface
{
    /**
     * @var ValidationError
     */
    private $validationError;

    /**
     * @param ValidationError $validationError
     */
    public function __construct($validationError)
    {
        $this->validationError = $validationError;
    }

    /**
     * @return Error[]
     */
    public function getErrors()
    {
        $delegateConverter = $this->getDelegateConvert($this->validationError);
        if ($delegateConverter instanceof ConverterInterface) {
            return $delegateConverter->getErrors();
        }

        return [$this->getUnknowError()];
    }

    /**
     * @param ValidationError $validationError
     * @return ConverterInterface|null
     */
    private function getDelegateConvert($validationError)
    {
        $converter = $this->validationError->getKeyword();
        $class = __NAMESPACE__ . '\\' . \ucfirst($converter) . 'Converter';
        if (\class_exists($class)) {
            return new $class($validationError);
        }

        return null;
    }

    /**
     * @return Error
     */
    private function getUnknowError()
    {
        return new Error(
            'Data is not valid',
            Error::DATA_VALIDATION_UNKNOW,
            $this->validationError
        );
    }
}