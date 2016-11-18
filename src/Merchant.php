<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class Merchant
 * @package Padosoft\AffiliateNetwork
 */
class Merchant
{
    /**
     * @var int
     */
    public $merchant_ID = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @method createInstance
     * @return obj istance
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new Merchant();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance Merchant - ' . $e->getMessage());
        }
        return $obj;
    }

}
