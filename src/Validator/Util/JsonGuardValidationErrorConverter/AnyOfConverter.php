<?php

namespace RREST\Validator\Util\JsonGuardValidationErrorConverter;

use League\JsonGuard\Constraint\DraftFour\Required;
use RREST\Error;

class AnyOfConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $errors = [];
        $fields = [];
        foreach ($this->validationError->getParameter() as $subconstraint) {
            if (isset($subconstraint->{Required::KEYWORD})) {
                $fields = \array_merge($subconstraint->{Required::KEYWORD}, $fields);
            }
        }

        $context = new \stdClass;
        $context->fields = $fields;
        $errors[] = new Error(
            \sprintf('The field %s is required', implode(' or/and ', $fields)),
            Error::DATA_VALIDATION_REQUIRED_ANYOF,
            $context
        );
        return $errors;
    }
}