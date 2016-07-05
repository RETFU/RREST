<?php

namespace RREST\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvalidJSONException extends BadRequestHttpException implements ErrorExceptionInterface
{
    /**
     * @var \RREST\Error[]
     */
    public $errors;

    /**
     * @param \RREST\Error[] $errors   List of errors
     * @param Exception|null $previous
     */
    public function __construct(array $errors, $message = 'Invalid', \Exception $previous = null, $code = 0)
    {
        $this->errors = $errors;
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
