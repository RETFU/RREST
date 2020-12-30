<?php

declare(strict_types=1);

namespace RREST\Exception;

use RREST\Error;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ErrorException extends HttpException
{
    /**
     * @var Error[]
     */
    public $errors;

    /**
     * @param Error[] $errors
     */
    public function __construct(array $errors, int $statusCode, string $message = null, \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $this->errors = $errors;

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    /**
     * @return Error[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
