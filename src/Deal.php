<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class Deal
 * @package Padosoft\AffiliateNetwork
 */
class Deal
{
    /**
     * @var int
     */
    public $merchant_ID=0;

    /**
     * @var int
     */
    public $network_ID=0;

    /**
     * @var int
     */
    public $type=0;

    /**
     * @var string
     */
    public $code='';

    /**
     * @var string
     */
    public $url='';

    /**
     * @var \DateTime
     */
    public $startDate;

    /**
     * @var \DateTime
     */
    public $endDate;

    /**
     * @var integer
     */
    public $deal_ID = 0;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $deal_type = '';

    /**
     * @var string
     */
    public $ppv = '';

    /**
     * @var string
     */
    public $ppc = '';

    /**
     * @method createInstance
     * @return obj istance
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new Deal();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance Deal - ' . $e->getMessage());
        }
        return $obj;
    }


}
