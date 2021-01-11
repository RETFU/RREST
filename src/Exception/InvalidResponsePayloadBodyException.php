<?php

namespace RREST\Exception;

class InvalidResponsePayloadBodyException extends ErrorException implements ErrorExceptionInterface
{
    /**
     * @param \RREST\Error[] $errors   List of errors
     * @param string         $message
     * @param \Exception     $previous
     * @param int            $code
     */
    public function __construct(array $errors, $message = 'Invalid', \Exception $previous = null, $code = 0)
    {
        parent::__construct($errors, 500, $message, $previous, [], $code);
    }
}
