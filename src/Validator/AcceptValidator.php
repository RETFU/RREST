<?php

namespace RREST\Validator;

use Negotiation\AcceptHeader;
use Negotiation\Exception\InvalidArgument;
use Negotiation\Negotiator;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class AcceptValidator implements ValidatorInterface
{
    /**
     * @var bool
     */
    private $isValidated = false;

    /**
     * @var array
     */
    private $validAccepts;

    /**
     * @var string
     */
    private $accept;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @param string $accept
     * @param array  $validAccepts
     */
    public function __construct($accept, $validAccepts)
    {
        if (empty($validAccepts)) {
            throw new \RuntimeException('No content type defined for this response');
        }

        $this->validAccepts = array_map('strtolower', $validAccepts);
        $this->accept = strtolower($accept);
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
        if ($this->isValidated) return;
        $accept = $this->getBestAccept();

        if (empty($accept)) {
            $this->isValidated = true;
            //see https://github.com/RETFU/RREST/issues/14
            return;
        }

        if (in_array($accept, $this->validAccepts) === false) {
            $this->exception = new NotAcceptableHttpException();
        }

        $this->isValidated = true;
    }

    /**
     * Find the best Accept header depending of priorities.
     *
     * @return string|null
     */
    public function getBestAccept()
    {
        if (empty($this->accept)) return null;

        try {
            $negotiaor = new Negotiator();
            $accept = $negotiaor->getBest($this->accept, $this->validAccepts);
            if($accept instanceof AcceptHeader === false) {
                return $this->accept;
            }
        } catch (\Exception $e) {
            return $this->accept;
        }

        return $accept->getValue();
    }
}