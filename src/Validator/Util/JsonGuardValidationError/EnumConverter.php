<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use RREST\Error;

class EnumConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->enum = $this->validationError->getParameter();
        $context->currentValue = $this->validationError->getData();
        return [new Error(
            \sprintf('The field %s must be one of this values: %s', $context->field, implode(', ', $context->enum)),
            Error::DATA_VALIDATION_ENUM,
            $context
        )];
    }
}