<?php

namespace RREST\Validator;

use RREST\APISpec\APISpecInterface;
use RREST\Exception\InvalidParameterException;
use RREST\Router\RouterInterface;

class ParameterValidator implements ValidatorInterface
{
    /**
     * @var bool
     */
    private $isValidated = false;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var APISpecInterface
     */
    private $apiSpec;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    protected $httpParametersTyped;

    /**
     * @param APISpecInterface $apiSpec
     * @param RouterInterface $router
     */
    public function __construct($apiSpec, $router)
    {
        $this->apiSpec = $apiSpec;
        $this->router = $router;
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

        $invalidParametersError = [];
        $parameters = $this->apiSpec->getParameters();
        foreach ($parameters as $parameter) {
            $value = $this->router->getParameterValue($parameter->getName());
            try {
                $castValue = $this->cast($value, $parameter->getType());
            } catch (\Exception $e) {
                $this->exception = new InvalidParameterException([
                    new Error(
                        $e->getMessage(),
                        $e->getCode()
                    ),
                ]);
            }
            try {
                $parameter->assertValue($castValue);
                $this->httpParametersTyped[$parameter->getName()] = $castValue;
            } catch (InvalidParameterException $e) {
                $invalidParametersError = array_merge(
                    $e->getErrors(),
                    $invalidParametersError
                );
            }
        }

        if (empty($invalidParametersError) == false) {
            $this->exception = new InvalidParameterException($invalidParametersError);
        }

        $this->isValidated = true;
    }

    /**
     * @return array
     */
    public function getHTTPParametersTyped()
    {
        return $this->httpParametersTyped;
    }

    /**
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    private function cast($value, $type)
    {
        $castValue = $value;
        if ($type == 'number') {
            $type = 'num';
        }

        if ($type != 'date') {
            $castValue = \CastToType::cast($value, $type, false, true);
        } else {
            //Specific case for date
            $castValue = new \DateTime($value);
        }

        //The cast not working, parameters is probably not this $type
        if (is_null($castValue)) {
            return $value;
        }

        return $castValue;
    }
}