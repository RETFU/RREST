<?php

namespace RREST\Validator\Util\JsonGuardValidationErrorConverter;

use RREST\Error;

interface ConverterInterface
{
    /**
     * @return Error[]
     */
    public function getErrors();
}