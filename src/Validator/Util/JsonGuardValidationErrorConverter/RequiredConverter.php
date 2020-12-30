<?php

namespace RREST\Validator\Util\JsonGuardValidationErrorConverter;

use RREST\Error;

class RequiredConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $errors = [];
        foreach ($this->validationError->getCause() as $field) {
            $context = new \stdClass;
            $context->field = $this->getFieldPath($this->validationError, $field);
            $errors[] = new Error(
                \sprintf('The field %s is required', $context->field),
                Error::DATA_VALIDATION_REQUIRED,
                $context
            );
        }
        return $errors;
    }
}
