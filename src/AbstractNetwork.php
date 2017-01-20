<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class AbstractNetwork
 * @package Padosoft\AffiliateNetwork
 */
abstract class AbstractNetwork
{
    /**
     * username
     * @var string
     */
    protected $username = '';

    /**
     * password
     * @var string
     */
    protected $password = '';

    /**
     * AbstractNetwork constructor.
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object,$methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Call protected/private property of a class.
     * @param $object
     * @param $propertyName
     *
     * @return mixed
     */
    public function invokeProperty(&$object,$propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

}
