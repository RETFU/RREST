<?php
namespace RREST;

use RREST\Exception\InvalidParameterException;
use RREST\Error;

class Parameter
{
    const TYPE_STRING   = 'string';
    const TYPE_NUMBER   = 'number';
    const TYPE_INTEGER  = 'integer';
    const TYPE_DATE     = 'date';
    const TYPE_BOOLEAN  = 'boolean';
    const TYPE_FILE     = 'file';

    /**
     * @var array
     */
    protected $validTypes = [
        self::TYPE_STRING,
        self::TYPE_NUMBER,
        self::TYPE_INTEGER,
        self::TYPE_DATE,
        self::TYPE_BOOLEAN,
        self::TYPE_FILE
    ];

    /**
     * Define types that can have a maximum or a minimum
     *
     * @var array
     */
    protected $minmaxTypes = [
        self::TYPE_STRING,
        self::TYPE_NUMBER,
        self::TYPE_INTEGER
    ];

    /**
     * The name of the parameter
     *
     * @var string
     */
    private $name;

    /**
     * The primitive type of the parameter
     *
     * @var string
     */
    private $type;

    /**
     * If the parameter is required
     *
     * @var boolean
     */
    protected $required;

    /**
     * List of valid values for the parameter (optional)
     *
     * @var array
     */
    private $enum;

    /**
     * A regular expression pattern for the string to match against (optional)
     *
     * @var string
     */
    private $validationPattern;

    /**
     * The minimum for a string length, integer or number (optional)
     *
     * @var integer
     */
    private $minimum;

    /**
     * The maximum for a string length, a integer or number (optional)
     *
     * @var integer
     */
    private $maximum;

    /**
     * @param string $name
     * @param string $type
     * @param boolean $required
     */
    public function __construct($name, $type, $required)
    {
        $this->setName($name);
        $this->setType($type);
        $this->setRequired($required);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @throws \Exception
     */
    public function setType($type)
    {
        if (!in_array($type, $this->validTypes)) {
            throw new InvalidQueryParameterTypeException($type, $this->validTypes);
        }

        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * @param array $enum
     */
    public function setEnum(array $enum)
    {
        $this->enum = $enum;
    }

    /**
     * @return string
     */
    public function getValidationPattern()
    {
        return $this->validationPattern;
    }

    /**
     * @param string $validationPattern
     */
    public function setValidationPattern($validationPattern)
    {
        $this->validationPattern = $validationPattern;
    }

    /**
     * @return integer
     */
    public function getMinimum()
    {
        return $this->minimum;
    }

    /**
     * @param integer $minimum
     *
     * @throws \Exception
     */
    public function setMinimum($minimum)
    {
        $this->minimum = (int) $minimum;
    }

    /**
     * @return integer
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * Set maximum
     */
    public function setMaximum($maximum)
    {
        $this->maximum = (int) $maximum;
    }

    /**
     * @return boolean
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = (bool) $required;
    }

    /**
     * @param mixed $param The value of the paramater to validate
     *
     * @throws \InvalidParameterException
     */
    public function assertValue($param)
    {
        // required?
        if (empty($param)) {
            if($this->getRequired()) {
                $this->throwInvalidParameter($this->getName().' is required');
            }
            return;
        }

        // good type?
        switch ($this->getType()) {
            case static::TYPE_BOOLEAN:
                if (!is_bool($param)) {
                    $this->throwInvalidParameter($this->getName().' is not a boolean');
                }
                break;
            case static::TYPE_DATE:
            //     if (\DateTime::createFromFormat('D, d M Y H:i:s T', $param) === false) {
            //         $this->throwInvalidParameter($this->getName().' is not a valid date', static::VAL_NOTDATE);
            //     }
                break;
            case static::TYPE_STRING:
                if (!is_string($param)) {
                    $this->throwInvalidParameter($this->getName().' is not a string');
                }
                break;
            case static::TYPE_INTEGER:
                if (!is_int($param)) {
                    $this->throwInvalidParameter($this->getName().' is not an integer');
                }
                break;
            case static::TYPE_NUMBER:
                if (!is_numeric($param)) {
                    $this->throwInvalidParameter($this->getName().' is not a number');
                }
                break;
            default:
                throw new \RuntimeException();
                break;
        }

        //min & max can only be apply to $this->minmaxTypes because make sense :)
        if(in_array($this->getType(), $this->minmaxTypes)) {
            $min = $this->getMinimum();
            $isNumeric = (
                $this->getType() === self::TYPE_NUMBER ||
                $this->getType() === self::TYPE_INTEGER
            );
            //FIXME: this condition is not working for numeric step, see price example
            if(empty($min) === false) {
                if(
                    ( $isNumeric && $min > $param ) ||
                    $min > strlen($param)
                ) {
                    $this->throwInvalidParameter($this->getName().' minimum size is '.$min);
                }
            }
            $max = $this->getMaximum();
            if(empty($max) === false) {
                if(
                    ( $isNumeric && $max < $param ) ||
                    $max < strlen($param)
                ) {
                    $this->throwInvalidParameter($this->getName().' maximum size is '.$max);
                }
            }
        }

        //valid with a pattern?
        $validationPattern = $this->getValidationPattern();
        if (!empty($validationPattern) &&
            preg_match('|'.$validationPattern.'|', $param) !== 1
        ) {
            $this->throwInvalidParameter($this->getName().' does not match the specified pattern: '.$validationPattern);
        }

        //in enum list?
        $enum = $this->getEnum();
        if (
            empty($enum) === false &&
            is_array($enum) &&
            in_array($param, $enum) === false
        ) {
            $this->throwInvalidParameter($this->getName().' must be one of the following: '.implode(', ', $enum));
        }
    }

    /**
     * @param  string $message
     * @param  string $code
     *
     * @throws InvalidParameterException
     */
    protected function throwInvalidParameter($message)
    {
        throw new InvalidParameterException([
            new Error($message, 'parameter-invalid')
        ]);
    }
}
