<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;

/**
 * Class Zanox
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Zanox extends AbstractNetwork implements NetworkInterface
{
    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Deal
     */
    public function getDeals(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array
    {
        // TODO: Implement getDeals() method.
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array
    {
        // TODO: Implement getSales() method.
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Stat
     */
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array
    {
        // TODO: Implement getStats() method.
    }
}
