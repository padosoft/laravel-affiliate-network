<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class Transaction
 * @package Padosoft\AffiliateNetwork
 */
class Transaction
{
    /**
     * @var int
     */
    public $network_ID=0;

    /**
     * @var int
     */
    public $merchant_ID=0;

    /**
     * @var int
     */
    public $status=0;

    /**
     * @var string
     */
    public $key='';

    /**
     * @var double
     */
    public $commission=0.00;
}
