<?php

namespace RREST\Exception;

use RREST\Error;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidXMLException extends BadRequestHttpException implements ErrorExceptionInterface
{
    /**
     * @var Error[]
     */
    public $errors;

    /**
     * @param Error[]  $errors   List of errors
     * @param \Exception|null $previous
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
