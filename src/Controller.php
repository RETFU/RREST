<?php

namespace RREST;

class Controller
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $routePath;

    /**
     * @var string
     */
    private $method;

    public function __construct($namespace, $routePath, $method)
    {
        $this->namespace = $namespace;
        $this->routePath = $routePath;
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getFullyQualifiedName()
    {
        $fullyQualifiedName = $this->namespace . '\\' . $this->getClassName($this->routePath);
        $this->assertClassExist($fullyQualifiedName);
        return $fullyQualifiedName;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    public function getActionMethodName()
    {
        $actionMethodName = strtolower($this->method) . 'Action';
        $this->assertMethodExist(
            $this->getFullyQualifiedName(),
            $actionMethodName
        );
        return $actionMethodName;
    }

    /**
     * Return the Controller class name depending of a route path
     *
     * By convention:
     *  - /item/{itemId}/ -> Item
     *  - /item/{itemId}/comment -> Item\Comment.
     *
     * @param string $routePath
     *
     * @return string
     */
    private function getClassName($routePath)
    {
        // remove URI parameters like controller/90/subcontroller/50
        $className = preg_replace('/\{[^}]+\}/', '', $routePath);
        $className = trim(str_replace('//', '/', $className));
        $className = trim($className, '/');
        $className = preg_replace('/[^a-zA-Z\d\/]/', '', $className);

        $chunks = explode('/', $className);
        $className = ucwords($className);

        if (count($chunks) > 1) {
            $chunks = array_map('ucwords', $chunks);
            $className = implode('\\', $chunks);
        }

        return $className;
    }

    /**
     * @param string $fullyQualifiedName
     * @throw RuntimeException
     *
     * @return string
     */
    private function assertClassExist($fullyQualifiedName)
    {
        if (class_exists($fullyQualifiedName) == false) {
            throw new \RuntimeException(
                $fullyQualifiedName . ' not found'
            );
        }
    }

    /**
     * @param string $fullyQualifiedName
     * @param string $actionMethodName
     *
     * @return string
     * @throw RuntimeException
     */
    private function assertMethodExist($fullyQualifiedName, $actionMethodName)
    {
        if (method_exists($fullyQualifiedName, $actionMethodName) == false) {
            throw new \RuntimeException(
                $fullyQualifiedName . '::' . $actionMethodName . ' method not found'
            );
        }
    }
}
