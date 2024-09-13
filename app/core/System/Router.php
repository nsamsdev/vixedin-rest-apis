<?php

namespace Vixedin\System;

use Slim\App;
use Slim\Container;

/**
 * @class Router
 */
class Router extends App
{
    /**
     * @var string
     */
    private static string $fullClassPath;

    /**
     * @var string
     */
    private static string $className;

    /**
     * @var array|Container
     */
    private array|Container $container;

    /**
     * @var array
     */
    private static array $dbSettings;

    /**
     * class constructor
     */
    public function __construct(array $settingsArray)
    {
        $this->container = $settingsArray;
        parent::__construct($this->container);
    }

    /**
     * sets the full path of the class i.e with namespaces.
     *
     * @param string $path
     */
    public static function setFullClassPath(string $path)
    {
        self::$fullClassPath = $path;
    }

    /**
     * returns the full path of class with namespaces.
     */
    public static function getFullClassPath(): string
    {
        return self::$fullClassPath;
    }

    /**
     * @return array
     */
    public static function getDbSettings(): array
    {
        return self::$dbSettings;
    }

    /**
     * @param array $dbSettings
     */
    public static function setDbSettings(array $dbSettings)
    {
        self::$dbSettings = $dbSettings;
    }

    /**
     * sets the active class name.
     *
     * @param string $className
     */
    public static function setClassName(string $className)
    {
        self::$className = $className;
    }

    /**
     * returns the active class name.
     */
    public static function getActiveClassName(): string
    {
        return self::$className;
    }
}
