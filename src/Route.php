<?php
namespace RREST;

class Route
{
    /**
     * @var string
     */
    private $path;

    /**
     * The verb of the Route
     * [GET, POST, PUT, PATCH, DELETE]
     *
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $authTypes;

    /**
     * @param string $path
     * @param string $method
     * @param string $authTypes
     */
    public function __construct($path, $method, $authTypes)
    {
        $this->path = $path;
        $this->method = $method;
        $this->authTypes = $authTypes;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return all auths type supported by the current route
     *
     * @return string[]
     */
    public function getAuthTypes()
    {
        return $this->authTypes;
    }
}
