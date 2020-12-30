<?php

namespace RREST\Validator\Util\JsonGuardValidationErrorConverter;

use RREST\Error;

class MaxLengthConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->maxLength = $this->validationError->getParameter();
        $context->currentValue = $this->validationError->getData();
        return [new Error(
            \sprintf('The field %s must be less than %s characters long', $context->field, $context->maxLength),
            Error::DATA_VALIDATION_MAXLENGTH,
            $context
        )];
    }
}
