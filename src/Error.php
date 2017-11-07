<?php

namespace RREST;

class Error
{
    const INVALID_JSON = 'INVALID_JSON';
    const DATA_VALIDATION_REQUIRED = 'DATA_VALIDATION_REQUIRED';
    const DATA_VALIDATION_REQUIRED_ANYOF = 'DATA_VALIDATION_REQUIRED_ANYOF';
    const DATA_VALIDATION_UNKNOW = 'DATA_VALIDATION_UNKNOW';
    const DATA_VALIDATION_MINLENGTH = 'DATA_VALIDATION_MINLENGTH';
    const DATA_VALIDATION_MAXLENGTH = 'DATA_VALIDATION_MAXLENGTH';
    const DATA_VALIDATION_FORMAT = 'DATA_VALIDATION_FORMAT';
    const DATA_VALIDATION_TYPE = 'DATA_VALIDATION_TYPE';
    const DATA_VALIDATION_ENUM = 'DATA_VALIDATION_ENUM';
    const DATA_VALIDATION_MINITEMS = 'DATA_VALIDATION_MINITEMS';
    const DATA_VALIDATION_MAXITEMS = 'DATA_VALIDATION_MAXITEMS';
    const DATA_VALIDATION_UNIQUEITEMS = 'DATA_VALIDATION_UNIQUEITEMS';
    const DATA_VALIDATION_ONEOF = 'DATA_VALIDATION_ONEOF';


    /**
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $code;

    /**
     * Add useful data to help debugging or to give more informations
     *
     * @var \stdClass
     */
    public $context;

    /**
     * @param string    $message
     * @param string    $code
     * @param \stdClass|null $context
     */
    public function __construct($message, $code, $context = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->context = $context;
    }
}
