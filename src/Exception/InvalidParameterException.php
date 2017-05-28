<?php

namespace RREST\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class InvalidParameterException extends UnprocessableEntityHttpException implements ErrorExceptionInterface
{
    /**
     * @var \RREST\Error[]
     */
    public $errors;

    /**
     * @param \RREST\Error[]  $errors   List of errors
     * @param \Exception|null $previous
     */
    public function __construct(array $errors, $message = '', \Exception $previous = null, $code = 0)
    {
        $this->errors = $errors;
        $errorMessages = [];
        foreach ($this->errors as $error) {
            $errorMessages[] = $error->message;
        }
        $message .= implode(', ', $errorMessages);
        parent::__construct($message, $previous, $code);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
