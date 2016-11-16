<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Interface NetworkInterface
 * @package Padosoft\AffiliateNetwork
 */
interface NetworkInterface
{
    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Deal
     */
    public function getDeals(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array;

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array;

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Stat
     */
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array ;
}
