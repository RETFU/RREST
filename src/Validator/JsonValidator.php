<?php

namespace RREST\Validator;

use League\JsonGuard\Validator;
use League\JsonReference\Dereferencer;
use RREST\Error;
use RREST\Exception\InvalidJSONException;
use RREST\Validator\Util\JsonGuardValidationErrorConverter\Converter;

class JsonValidator
{
    /**
     * @var Error[]
     */
    private $errors = [];

    /**
     * @var bool
     */
    private $isValidated = false;

    /**
     * @var string
     */
    private $jsonValue;

    /**
     * @var string
     */
    private $jsonSchema;

    /**
     * @param string $jsonValue
     * @param string $jsonSchema
     */
    public function __construct($jsonValue, $jsonSchema)
    {
        $this->jsonValue = $jsonValue;
        $this->jsonSchema = $jsonSchema;
    }


    /**
     * @return bool
     */
    public function fails()
    {
        return empty($this->getErrors()) === false;
    }

    /**
     * @return Error[]
     */
    public function getErrors()
    {
        $this->validate();
        return $this->errors;
    }

    public function validate()
    {
        if ($this->isValidated) return;

        $schema = Dereferencer::draft4()->dereference(
            $this->getJsonFromString($this->jsonSchema)
        );
        $json = $this->getJsonFromString($this->jsonValue);
        $validator = new Validator($json, $schema);
        if ($validator->fails()) {
            $this->errors = [];
            foreach ($validator->errors() as $jsonGuardError) {
                $this->errors = \array_merge(
                    $this->errors,
                    (new Converter($jsonGuardError))->getErrors()
                );
            }
        }
    }

    /**
     * @throws InvalidJSONException
     * @param $value
     * @return \stdClass|array
     */
    private function getJsonFromString($value)
    {
        $json = json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = new Error(ucfirst(json_last_error_msg()), Error::INVALID_JSON);
            throw new InvalidJSONException([$error]);
        }

        return $json;
    }

}