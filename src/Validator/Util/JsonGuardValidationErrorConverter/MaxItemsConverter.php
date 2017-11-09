<?php

namespace RREST\Validator\Util\JsonGuardValidationErrorConverter;

use RREST\Error;

class MaxItemsConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->maxItems = $this->validationError->getParameter();
        $context->currentValues = $this->validationError->getData();
        return [new Error(
            \sprintf('The field %s must contain less than  %s item(s)', $context->field, $context->maxItems),
            Error::DATA_VALIDATION_MAXITEMS,
            $context
        )];
    }
}