<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use RREST\Error;

class OneOfConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->rules = $this->validationError->getParameter();
        $context->currentValue = $this->validationError->getData();
        return [new Error(
            \sprintf('The field %s don\'t follow any rules', $context->field),
            ERROR::DATA_VALIDATION_ONEOF,
            $context
        )];
    }
}