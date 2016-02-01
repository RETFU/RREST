<?php

namespace RREST\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Thrown when a validation check fails.
 */
class InvalidException extends UnprocessableEntityHttpException
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
     * @return RREST\Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
