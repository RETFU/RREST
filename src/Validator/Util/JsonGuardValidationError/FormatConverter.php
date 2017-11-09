<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use RREST\Error;

class FormatConverter extends ConverterAbstract
{
    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($this->validationError, '');
        $context->currentValue = $this->validationError->getData();
        switch ($this->validationError->getParameter()) {
            case 'date-time':
                $message = \sprintf('The field %s must be a valid date, following RFC3339 (example: 2017-11-08T15:37:26+00:00)', $context->field);
                break;
            case 'uri':
                $message = \sprintf('The field %s must be a valid URL', $context->field);
                break;
            case 'email':
                $message = \sprintf('The field %s must be a valid email', $context->field);
                break;
            case 'ipv4':
                $message = \sprintf('The field %s must be a valid ipv4', $context->field);
                break;
            case 'ipv6':
                $message = \sprintf('The field %s must be a valid ipv6', $context->field);
                break;
            case 'hostname':
                $message = \sprintf('The field %s must be a valid hostname', $context->field);
                break;
            default:
                $message = 'Invalid format';
        }
        return [new Error(
            $message,
            Error::DATA_VALIDATION_FORMAT,
            $context
        )];
    }
}