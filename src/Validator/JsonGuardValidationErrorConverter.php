<?php

namespace RREST\Validator;

use League\JsonGuard\Constraint\DraftFour\AnyOf;
use League\JsonGuard\Constraint\DraftFour\Required;
use League\JsonGuard\ValidationError;
use RREST\Error;

class JsonGuardValidationErrorConverter
{
    /**
     * @var ValidationError
     */
    private $validationError;

    /**
     * @param ValidationError $validationError
     */
    public function __construct($validationError)
    {
        $this->validationError = $validationError;
    }

    /**
     * @return Error[]
     */
    public function getErrors()
    {
        $method = 'get'.\ucfirst(\mb_strtolower($this->validationError->getKeyword())).'Errors';
        if(\method_exists($this, $method)) {
            return $this->$method($this->validationError);
        }

        return [new Error(
            'Data is not valid',
            ERROR::DATA_VALIDATION_UNKNOW,
            $this->validationError
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getRequiredErrors($validationError)
    {
        $errors = [];
        foreach($validationError->getCause() as $field) {
            $context = new \stdClass;
            $context->field = $this->getFieldPath($validationError, $field);
            $errors[] = new Error(
                \sprintf('The field %s is required', $context->field),
                ERROR::DATA_VALIDATION_REQUIRED,
                $context
            );
        }
        return $errors;
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getAnyofErrors($validationError)
    {
        $errors = [];
        $fields = [];
        foreach($validationError->getParameter() as $subconstraint) {
            if(isset($subconstraint->{Required::KEYWORD})) {
                $fields = \array_merge($subconstraint->{Required::KEYWORD}, $fields);
            }
        }

        $context = new \stdClass;
        $context->fields = $fields;
        $errors[] = new Error(
            \sprintf('The field %s is required', implode(' or/and ', $fields) ),
            ERROR::DATA_VALIDATION_REQUIRED_ANYOF,
            $context
        );
        return $errors;
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getMinLengthErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        $context->minLength = $validationError->getParameter();
        return [new Error(
            \sprintf('The field %s must be at least %s characters long', $context->field, $context->minLength),
            ERROR::DATA_VALIDATION_MINLENGTH,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getMaxLengthErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        $context->minLength = $validationError->getParameter();
        return [new Error(
            \sprintf('The field %s must be less than %s characters long', $context->field, $context->maxLength),
            ERROR::DATA_VALIDATION_MINLENGTH,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getTypeErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        $context->type = $validationError->getParameter();
        return [new Error(
            \sprintf('The type of the field %s is not valid, must be a/an %s', $context->field,  implode(' or ',  (array)$context->type)),
            ERROR::DATA_VALIDATION_TYPE,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getEnumErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        $context->enum = $validationError->getParameter();
        return [new Error(
            \sprintf('The field %s must be one of this values: %s', $context->field, implode(', ', $context->enum )),
            ERROR::DATA_VALIDATION_ENUM,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getMinitemsErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        $context->minItems = $validationError->getParameter();
        return [new Error(
            \sprintf('The field %s must contain at least %s item(s)', $context->field, $context->minItems),
            ERROR::DATA_VALIDATION_MINITEMS,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getMaxitemsErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        $context->maxItems = $validationError->getParameter();
        return [new Error(
            \sprintf('The field %s must contain less than  %s item(s)', $context->field, $context->maxItems),
            ERROR::DATA_VALIDATION_MAXITEMS,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getUniqueitemsErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        return [new Error(
            \sprintf('The field %s must not contain duplicates values', $context->field),
            ERROR::DATA_VALIDATION_UNIQUEITEMS,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getOneofErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        $context->rules = $validationError->getParameter();
        return [new Error(
            \sprintf('The field %s don\'t follow any rules', $context->field),
            ERROR::DATA_VALIDATION_ONEOF,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getPatternErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        $context->pattern = $validationError->getParameter();
        return [new Error(
            \sprintf('The field %s must match the pattern %s', $context->field, $context->pattern),
            ERROR::DATA_VALIDATION_UNIQUEITEMS,
            $context
        )];
    }

    /**
     * @param ValidationError $validationError
     * @return array
     */
    private function getFormatErrors($validationError)
    {
        $context = new \stdClass;
        $context->field = $this->getFieldPath($validationError, '');
        switch ($validationError->getParameter()) {
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
            ERROR::DATA_VALIDATION_FORMAT,
            $context
        )];
    }

    /**
     * @param $validationError
     * @param $field
     * @return string
     */
    private function getFieldPath($validationError, $field)
    {
        $path = '';
        $dataPath = $validationError->getDataPath();
        if($dataPath !== '/') {
            $path = \str_replace('/', '.',\substr($dataPath, 1)).'.';
        }
        return rtrim($path.$field, '.');
    }
}