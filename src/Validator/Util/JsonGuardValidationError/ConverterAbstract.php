<?php

namespace RREST\Validator\Util\JsonGuardValidationError;

use League\JsonGuard\ValidationError;

abstract class ConverterAbstract implements ConverterInterface
{
    /**
     * @var ValidationError
     */
    protected $validationError;

    /**
     * @param ValidationError $validationError
     */
    public function __construct($validationError)
    {
        $this->validationError = $validationError;
    }

    /**
     * @param ValidationError $validationError
     * @param string $field
     * @return string
     */
    protected function getFieldPath($validationError, $field)
    {
        $path = '';
        $dataPath = $validationError->getDataPath();
        if ($dataPath !== '/') {
            $path = \str_replace('/', '.', \substr($dataPath, 1)) . '.';
        }
        return rtrim($path . $field, '.');
    }
}