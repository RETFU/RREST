<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use RREST\Error;

class TypeConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->type = $this->validationError->getParameter();
        $context->currentValue = $this->validationError->getData();
        return [new Error(
            \sprintf('The type of the field %s is not valid, must be a/an %s', $context->field, implode(' or ', (array)$context->type)),
            Error::DATA_VALIDATION_TYPE,
            $context
        )];
    }
}