<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use RREST\Error;

class UniqueItemsConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->currentValues = $this->validationError->getData();
        return [new Error(
            \sprintf('The field %s must not contain duplicates values', $context->field),
            Error::DATA_VALIDATION_UNIQUEITEMS,
            $context
        )];
    }
}
