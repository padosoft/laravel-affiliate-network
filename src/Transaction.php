<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class Transaction
 * @package Padosoft\AffiliateNetwork
 */
class Transaction
{
    /**
     * @var string
     */
    public $currency = '';

    /**
     * @var string
     */
    public $status = '';

    /**
     * @var float
     */
    public $amount = 0.00;

    /**
     * @var string
     */
    public $custom_ID = '';

    /**
     * @var string
     */
    public $title = '';

    /**
     * @var string
     */
    public $unique_ID = '';

    /**
     * @var double
     */
    public $commission = 0.00;

    /**
     * @var string
     */
    public $date = '';

    /**
     * @var int
     */
    public $merchant_ID = 0;

    /**
     * @var boolean
     */
    public $approved = false;

    /**
     * @method createInstance
     * @return obj istance
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new Transaction();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance Transaction - ' . $e->getMessage());
        }
        return $obj;
    }

}
