<?php

namespace RREST;

use RREST\Exception\InvalidParameterException;

class Parameter
{
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_STRING = 'string';
    const TYPE_DATE_ONLY = 'date-only';
    const TYPE_TIME_ONLY = 'time-only';
    const TYPE_DATETIME_ONLY = 'datetime-only';
    const TYPE_DATETIME = 'datetime';
    const TYPE_FILE = 'file';
    const TYPE_INTEGER = 'integer';

    /**
     * @var string[]
     */
    protected $validTypes = [
        self::TYPE_NUMBER,
        self::TYPE_STRING,
        self::TYPE_BOOLEAN,
        self::TYPE_DATE_ONLY,
        self::TYPE_TIME_ONLY,
        self::TYPE_DATETIME_ONLY,
        self::TYPE_DATETIME,
        self::TYPE_FILE,
        self::TYPE_INTEGER,
    ];

    /**
     * Define types that can have a maximum or a minimum.
     *
     * @var string[]
     */
    protected $minmaxTypes = [
        self::TYPE_STRING,
        self::TYPE_NUMBER,
        self::TYPE_INTEGER,
    ];

    /**
     * The name of the parameter.
     *
     * @var string
     */
    private $name;

    /**
     * The primitive type of the parameter.
     *
     * @var string
     */
    private $type;

    /**
     * If the parameter is required.
     *
     * @var bool
     */
    protected $required;

    /**
     * List of valid values for the parameter (optional).
     *
     * @var string[]
     */
    private $enum;

    /**
     * A regular expression pattern for the string to match against (optional).
     *
     * @var string
     */
    private $validationPattern;

    /**
     * The minimum for a string length, integer or number (optional).
     *
     * @var int|null
     */
    private $minimum;

    /**
     * The maximum for a string length, a integer or number (optional).
     *
     * @var int|null
     */
    private $maximum;

    /**
     * A valid DateTime format
     * Default RFC2616.
     *
     * @var string
     */
    private $dateFormat = 'D, d M Y H:i:s T';

    /**
     * @param string $name
     * @param string $type
     * @param bool   $required
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
     * @throws \RuntimeException
     */
    public function setType($type)
    {
        if (!in_array($type, $this->validTypes)) {
            throw new \RuntimeException(
                $type.' is not a valid type ('.implode(',', $this->validTypes).')'
            );
        }

        $this->type = $type;
    }

    /**
     * @return string[]
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * @param array string[]
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
     * @return int
     */
    public function getMinimum()
    {
        return $this->minimum;
    }

    /**
     * @param int $minimum
     *
     * @throws \Exception
     */
    public function setMinimum($minimum)
    {
        $this->minimum = \CastToType::cast($minimum, 'integer', false, true);
    }

    /**
     * @return int
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * Set maximum.
     */
    public function setMaximum($maximum)
    {
        $this->maximum = \CastToType::cast($maximum, 'integer', false, true);
    }

    /**
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = (bool) $required;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * @param string $type
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    /**
     * @param mixed $value The value of the paramater to validate
     *
     * @throws \RREST\Exception\InvalidParameterException
     */
    public function assertValue($value)
    {
        // required?
        if (empty($value)) {
            if ($this->getRequired()) {
                $this->throwInvalidParameter($this->getName().' is required');
            }
            //no need to continue, the value is empty & not required
            return;
        }

        // good type?
        switch ($this->getType()) {
            case static::TYPE_BOOLEAN:
                if (!is_bool($value)) {
                    $this->throwInvalidParameter($this->getName().' is not a boolean');
                }
                break;
            case static::TYPE_DATETIME:
                if ($value instanceof \DateTime === false) {
                    $this->throwInvalidParameter($this->getName().' is not a valid date');
                }
                break;
            case static::TYPE_DATE_ONLY:
            case static::TYPE_TIME_ONLY:
            case static::TYPE_DATETIME_ONLY:
                $this->throwInvalidParameter(
                    $this->getType().' is not supported yet in RREST. Use datetime or feel free to contribute it'
                );
                break;
            case static::TYPE_STRING:
                if (!is_string($value)) {
                    $this->throwInvalidParameter($this->getName().' is not a string');
                }
                break;
            case static::TYPE_INTEGER:
                if (!is_int($value)) {
                    $this->throwInvalidParameter($this->getName().' is not an integer');
                }
                break;
            case static::TYPE_NUMBER:
                if (!is_numeric($value)) {
                    $this->throwInvalidParameter($this->getName().' is not a number');
                }
                break;
            default:
                throw new \RuntimeException();
                break;
        }

        //min & max can only be apply to $this->minmaxTypes because make sense :)
        if (in_array($this->getType(), $this->minmaxTypes)) {
            $isNumeric = (
                $this->getType() === self::TYPE_NUMBER ||
                $this->getType() === self::TYPE_INTEGER
            );
            $isString = $this->getType() === self::TYPE_STRING;
            $min = $this->getMinimum();
            if (empty($min) === false) {
                if (
                    ($isNumeric && $min > $value) ||
                    ($isString && $min > strlen($value))
                ) {
                    $this->throwInvalidParameter($this->getName().' minimum size is '.$min);
                }
            }
            $max = $this->getMaximum();
            if (empty($max) === false) {
                if (
                    ($isNumeric && $max < $value) ||
                    ($isString && $max < strlen($value))
                ) {
                    $this->throwInvalidParameter($this->getName().' maximum size is '.$max);
                }
            }
        }

        //valid with a pattern?
        $validationPattern = $this->getValidationPattern();
        if (!empty($validationPattern) &&
            preg_match('|'.$validationPattern.'|', $value) !== 1
        ) {
            $this->throwInvalidParameter($this->getName().' does not match the specified pattern: '.$validationPattern);
        }

        //in enum list?
        $enum = $this->getEnum();
        if (
            empty($enum) === false &&
            is_array($enum) &&
            in_array($value, $enum, true) === false
        ) {
            $this->throwInvalidParameter($this->getName().' must be one of the following: '.implode(', ', $enum));
        }
    }

    /**
     * @param string $message
     *
     * @throws InvalidParameterException
     */
    protected function throwInvalidParameter($message)
    {
        throw new InvalidParameterException([
            new Error($message, 'parameter-invalid'),
        ]);
    }
}
