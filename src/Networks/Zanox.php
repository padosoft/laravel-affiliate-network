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
     * @var object
     */
    private $_network = null;

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password)
    {
        $this->_network = new \Oara\Network\Publisher\Zanox;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
    {
        $credentials = array();
        $credentials["connectid"] = $this->username;
        $credentials["secretkey"] = $this->password;
        $this->_network->login($credentials);
        if ($this->_network->checkConnection()) {
            return true;
        }
        
        return false;
    }

    /**
     * @return array of Merchants
     */
    public function getMerchants() : array
    {
        return $this->_network->getMerchantList();
    }

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
