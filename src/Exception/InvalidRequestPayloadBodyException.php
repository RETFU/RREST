<?php

namespace RREST\Exception;

class InvalidRequestPayloadBodyException extends ErrorException implements ErrorExceptionInterface
{
    /**
     * @param \RREST\Error[]  $errors   List of errors
     * @param \Exception|null $previous
     */
    public function __construct(array $errors, $message = 'Invalid', \Exception $previous = null, $code = 0)
    {
        parent::__construct($errors, 500, $message, $previous, [], $code);
    }
}
