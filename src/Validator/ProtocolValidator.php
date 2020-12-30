<?php

namespace RREST\Validator;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ProtocolValidator implements ValidatorInterface
{
    /**
     * @var bool
     */
    private $isValidated = false;

    /**
     * @var array
     */
    private $availableProtocols;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @param string $protocol
     * @param array $availableProtocols
     */
    public function __construct($protocol, $availableProtocols)
    {
        $this->availableProtocols = array_map('strtolower', $availableProtocols);
        $this->protocol = strtolower($protocol);
    }

    /**
     * @return bool
     */
    public function fails()
    {
        return empty($this->getException()) === false;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        $this->validate();
        return $this->exception;
    }

    public function validate()
    {
        if ($this->isValidated) {
            return;
        }

        if (in_array($this->protocol, $this->availableProtocols) === false) {
            $this->exception = new AccessDeniedHttpException();
        }

        $this->isValidated = true;
    }
}
