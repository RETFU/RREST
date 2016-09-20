<?php

namespace RREST;

class Error
{
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
     * @param \stdClass $context
     */
    public function __construct($message = null, $code = null, $context = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->context = $context;
    }
}
