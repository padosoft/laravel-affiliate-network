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
}
