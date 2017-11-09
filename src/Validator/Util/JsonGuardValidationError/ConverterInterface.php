<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use RREST\Error;

interface ConverterInterface
{
    /**
     * @return Error[]
     */
    public function getErrors();
}