<?php

namespace RREST\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Thrown when a validation check fails.
 */
class InvalidException extends UnprocessableEntityHttpException
{
    /**
     * @var array
     */
    public $errors;

    /**
     * @param array          $errors   List of errors
     * @param Exception|null $previous
     */
    public function __construct(array $errors, $message = 'Invalid', \Exception $previous = null, $code = 0)
    {
        $this->errors = $errors;
        parent::__construct($message, $previous, $code);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
