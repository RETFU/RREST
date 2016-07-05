<?php

namespace RREST\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidResponsePayloadBodyException extends HttpException implements ErrorExceptionInterface
{
    /**
     * @var \RREST\Error[]
     */
    public $errors;

    /**
     * @param \RREST\Error[]  $errors   List of errors
     * @param string $message
     * @param \Exception $previous
     * @param integer $code
     */
    public function __construct(array $errors, $message = 'Invalid', \Exception $previous = null, $code = 0)
    {
        $this->errors = $errors;
        parent::__construct(500, $message, $previous, [], $code);
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
