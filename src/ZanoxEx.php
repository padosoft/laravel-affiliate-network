<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */


namespace Padosoft\AffiliateNetwork;
use Oara\Network\Publisher\Zanox as ZanoxOara;

class ZanoxEx extends ZanoxOara
{
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
    /**
     * get Sales updated/created on the date passed
     * @param $date
     * @param $page
     * @param $pageSize
     * @param int $iteration
     *
     * @return array
     */
    protected function getSales($date, $page, $pageSize, $iteration = 0)
    {
        $transactionList = array();
        try {
            $transactionList = $this->_apiClient->getSales($date, 'modifiedDate', null, null, null, $page, $pageSize, $iteration);
        } catch (\Exception $e) {
            $iteration++;
            if ($iteration < 5) {
                $transactionList = self::getSales($date, $page, $pageSize, $iteration);
            }

        }
        return $transactionList;

    }

    public function getApiClient(){
        return $this->_apiClient;
    }
}