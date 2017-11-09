<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use RREST\Error;

class PatternConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->pattern = $this->validationError->getParameter();
        $context->currentValues = $this->validationError->getData();
        return [new Error(
            \sprintf('The field %s must match the pattern %s', $context->field, $context->pattern),
            Error::DATA_VALIDATION_UNIQUEITEMS,
            $context
        )];
    }
}
