<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class NetworkManager
 * @package Padosoft\AffiliateNetwork
 */
class NetworkManager
{
    /**
     * Network/api
     * @var NetworkInterface
     */
    protected $network;

    /**
     * Register the dependencies
     * @param NetworkInterface $network
     */
    public function __construct(NetworkInterface $network)
    {
        $this->setNetwork($network);
    }

    /**
     * Set the network / api to use
     * @param NetworkInterface $network
     */
    public function setNetwork(NetworkInterface $network)
    {
        $this->network = $network;
    }

    /**
     * Get the network
     * @return NetworkInterface
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Deal
     */
    public function getDeals(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array
    {
        return $this->network->getDeals($dateFrom, $dateTo, $merchantID);
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array
    {
        return $this->network->getSales($dateFrom, $dateTo, $merchantID);
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Stat
     */
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array
    {
        return $this->network->getStats($dateFrom, $dateTo, $merchantID);
    }
}
