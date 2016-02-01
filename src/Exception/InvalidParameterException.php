<?php

namespace RREST\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class InvalidParameterException extends UnprocessableEntityHttpException implements ErrorExceptionInterface
{
    /**
     * @var RREST\Error[]
     */
    public $errors;

    /**
     * @param RREST\Error[]  $errors   List of errors
     * @param Exception|null $previous
     */
    public function __construct(array $errors, $message = 'Invalid', \Exception $previous = null, $code = 0)
    {
        $this->errors = $errors;
        parent::__construct($message, $previous, $code);
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
