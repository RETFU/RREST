<?php

namespace RREST\Exception;

use RREST\Error;

class InvalidXMLException extends ErrorException implements ErrorExceptionInterface
{
    /**
     * @param Error[]         $errors   List of errors
     * @param \Exception|null $previous
     */
    public function __construct(array $errors, $message = 'Invalid', \Exception $previous = null, $code = 0)
    {
        parent::__construct($errors, 400, $message, $previous, [], $code);
    }
}
