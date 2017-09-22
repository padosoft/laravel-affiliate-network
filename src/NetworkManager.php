<?php

namespace Padosoft\AffiliateNetwork;

use Padosoft\AffiliateNetwork\DealsResultset;

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
    protected $networks = [];
    protected $avaliable_networks = [];

    /**
     * Register the dependencies
     */
    public function __construct()
    {
        //$this->setNetwork($network);
        $this->loadAvailableNetworks();
    }

    /**
     * Add a network / api to use
     *
     * @param NetworkInterface $network
     * @param string $network_alias
     */
    public function addNetwork(NetworkInterface $network, $network_alias = '')
    {
        if (trim($network_alias)==''){
            $path = explode('\\', get_class($network));
            $network_alias = array_pop($path);
        }
        if (!array_key_exists( $network_alias, $this->networks )) {
            $this->networks[$network_alias] = $network;
        }
        $this->avaliable_networks[$network_alias]=get_class($network);

    }

    /**
     * Get a network
     * @return NetworkInterface|null
     */
    public function getNetwork($network_alias)
    {
        if (!$this->hasNetwork($network_alias)) {
            return null;
        }
        return $this->networks[$network_alias];
    }

    /**
     * @return array
     */
    protected function loadAvailableNetworks(){
        $classes=scandir(__DIR__.'/Networks');
        foreach ($classes AS $network_class){
            if ($network_class=='.' || $network_class=='..'){
                continue;
            }
            $class = new \ReflectionClass(__NAMESPACE__.'\\Networks\\'.substr($network_class,0,-4));


            $this->avaliable_networks[$class->getShortName()]=$class->getName();
        }
    }

    public function getAvailableNetworks():array {
        return $this->avaliable_networks;
    }

    public function hasNetwork($network_alias){
        if (!array_key_exists($network_alias, $this->networks ) && array_key_exists($network_alias, $this->avaliable_networks)){
            $fully_className=$this->avaliable_networks[$network_alias];
            $this->networks[$network_alias]= new $fully_className('','');
        }
        return array_key_exists($network_alias, $this->networks );
    }

    /**
     * @param $network_alias
     * @param int $merchantID
     *
     * @return \Padosoft\AffiliateNetwork\DealsResultset
     */
    public function getDeals($network_alias, int $merchantID = 0, int $page = 0, int $items_per_page = 0 ): DealsResultset
    {
        if (!$this->hasNetwork($network_alias)) {
            return DealsResultset::createInstance();
        }
        if (!isIntegerPositive($merchantID)){
            $merchantID=NULL;
        }
        return $this->networks[$network_alias]->getDeals( $merchantID, $page, $items_per_page );
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     *
     * @return array of Transaction
     */
    public function getSales(string $network_alias,\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()): array
    {
        if (!$this->hasNetwork($network_alias)) {
            return [];
        }
        return $this->networks[$network_alias]->getSales( $dateFrom, $dateTo, $arrMerchantID );
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     *
     * @return array of Stat
     */
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0): array
    {
        if (!$this->hasNetwork($network_alias)) {
            return [];
        }
        return $this->networks[$network_alias]->getStats( $dateFrom, $dateTo, $merchantID );
    }

    public function getMerchants(string $network_alias) : array{
        if (!$this->hasNetwork($network_alias)) {
            return [];
        }
        return $this->networks[$network_alias]->getMerchants();
    }

    public function checkLogin(string $network_alias): bool{
        if (!$this->hasNetwork($network_alias)) {
            return false;
        }
        return $this->networks[$network_alias]->checkLogin();
    }
    public function login(string $network_alias,string $username, string $password,string $idSite=''): bool{
        if (!$this->hasNetwork($network_alias)) {
            return false;
        }
        return $this->networks[$network_alias]->login($username,$password,$idSite);
    }

    public function getTrackingParameter(string $network_alias):string {
        if (!$this->hasNetwork($network_alias)) {
            return false;
        }
        return $this->networks[$network_alias]->getTrackingParameter();
    }
}
