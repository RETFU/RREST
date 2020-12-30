<?php

namespace RREST\Validator\Util\JsonGuardValidationErrorConverter;

use RREST\Error;

class MinItemsConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->minItems = $this->validationError->getParameter();
        $context->currentValues = $this->validationError->getData();
        return [new Error(
            \sprintf('The field %s must contain at least %s item(s)', $context->field, $context->minItems),
            Error::DATA_VALIDATION_MINITEMS,
            $context
        )];
    }
}
