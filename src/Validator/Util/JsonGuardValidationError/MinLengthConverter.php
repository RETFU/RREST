<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use RREST\Error;

class MinLengthConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->minLength = $this->validationError->getParameter();
        $context->currentValue = $this->validationError->getData();
        return [new Error(
            \sprintf('The field %s must be at least %s characters long', $context->field, $context->minLength),
            Error::DATA_VALIDATION_MINLENGTH,
            $context
        )];
    }
}