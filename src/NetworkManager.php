<?php

namespace Padosoft\AffiliateNetwork;

use Padosoft\AffiliateNetwork\DealsResultset;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;


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
    protected $loggerService = null;

    /**
     * Register the dependencies
     */
    public function __construct()
    {
        $this->loadAvailableNetworks();
    }

    public function logCapture()
    {
        if (!is_null($this->loggerService)) {
            if (ob_get_contents() != '') {
                // flush any pending log
                $this->logFlush();
            }
            // Initialize output capture
            ob_start();
        }
    }

    /**
     * Flush the captured log messages
     */
    public function logFlush()
    {
        if (!is_null($this->loggerService)) {
            $messages = @ob_get_flush();
            $messages = str_replace('<BR>',PHP_EOL, $messages);
            $a_messages = explode(PHP_EOL, $messages);
            foreach ($a_messages as $message) {
                if (!empty(trim($message))) {
                    if (strpos(strtolower($message), 'error') !== false) {
                        $this->loggerService->error($message);
                    }
                    elseif (strpos(strtolower($message), 'warning') !== false) {
                        $this->loggerService->warning($message);
                    }
                    elseif (strpos(strtolower($message), 'debug') !== false) {
                        $this->loggerService->debug($message);
                    }
                    else {
                        $this->loggerService->info($message);
                    }
                }
            }
        }
    }

    /**
     * Inject an external Log service compatible with Psr\Log interface
     * @param Psr\Log\LoggerInterface $loggerService
     */
    public function setLogger(\Psr\Log\LoggerInterface $loggerService)
    {
        $this->loggerService = $loggerService;
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

    /**
     * @param $network_alias
     * @param string $username
     * @param string $password
     * @param string $id_site
     * @param string $country
     * @return bool
     */
    public function hasNetwork($network_alias, $username = '', $password = '', string $id_site = '', string $country = '') {
        if (!array_key_exists($network_alias, $this->networks ) && array_key_exists($network_alias, $this->avaliable_networks)){
            $fully_className=$this->avaliable_networks[$network_alias];
            $this->networks[$network_alias]= new $fully_className($username, $password, $id_site, $country);
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
        $this->logCapture();
        $a_deals = $this->networks[$network_alias]->getDeals( $merchantID, $page, $items_per_page );
        $this->logFlush();
        return $a_deals;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     *
     * @return array of Transaction
     */
    public function getSales(string $network_alias, \DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()): array
    {
        if (!$this->hasNetwork($network_alias)) {
            return [];
        }
        $this->logCapture();
        $a_sales = $this->networks[$network_alias]->getSales( $dateFrom, $dateTo, $arrMerchantID );
        $this->logFlush();
        return $a_sales;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     *
     * @return array of Stat
     */
    public function getStats(string $network_alias, \DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0): array
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
        $this->logCapture();
        $a_merchants = $this->networks[$network_alias]->getMerchants();
        $this->logFlush();
        return $a_merchants;

    }

    public function checkLogin(string $network_alias): bool{
        if (!$this->hasNetwork($network_alias)) {
            return false;
        }
        $this->logCapture();
        $success = $this->networks[$network_alias]->checkLogin();
        $this->logFlush();
        return $success;
    }

    public function login(string $network_alias, string $username, string $password, string $id_site = '', string $country = ''): bool{
        if (!$this->hasNetwork($network_alias, $username, $password, $id_site, $country)) {
            return false;
        }
        if (!$this->networks[$network_alias]->checkLogin()) {
            $this->logCapture();
            $success = $this->networks[$network_alias]->login($username, $password, $id_site, $country);
            $this->logFlush();
            return $success;
        }
        return true;
    }

    public function getTrackingParameter(string $network_alias):string {
        if (!$this->hasNetwork($network_alias)) {
            return false;
        }
        return $this->networks[$network_alias]->getTrackingParameter();
    }
}
