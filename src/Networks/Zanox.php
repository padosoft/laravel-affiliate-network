<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/Zanox/Zapi/ApiClient.php";

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
    private $_apiClient = null;
    private $_username = '';
    private $_password = '';
    private $_logged = false;



    /**
     * @method __construct
     */
    public function __construct(string $username, string $password)
    {
        $this->_network = new \Oara\Network\Publisher\Zanox;
        $this->_username = $username;
        $this->_password = $password;
        $credentials = array();
        $credentials["connectid"] = $this->_username;
        $credentials["secretkey"] = $this->_password;
        $this->_network->login($credentials);
        $this->_apiClient = $this->invokeProperty($this->_network,'_apiClient');
        if ($this->_network->checkConnection()) {
            $this->_logged=true;

        }

    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
    {
        return $this->_logged;
    }

    /**
     * @return array of Merchants
     */
    public function getMerchants() : array
    {
        if (!$this->checkLogin()){
            return array();
        }
        $arrResult = array();
        $merchantList = $this->_network->getMerchantList();
        foreach($merchantList as $merchant) {
            $Merchant = Merchant::createInstance();
            $Merchant->merchant_ID = $merchant['cid'];
            $Merchant->name = $merchant['name'];
            $arrResult[] = $Merchant;
        }

        return $arrResult;
    }

    /**
     * @param int $merchantID
     * @return array of Deal
     */
    public function getDeals(int $merchantID = 0) : array
    {
        if (!$this->checkLogin()){
            return array();
        }
        $this->_apiClient->setConnectId($this->_username);
        $this->_apiClient->setSecretKey($this->_password);
        $arrResponse = json_decode($this->_apiClient->getAdmedia(), true);
        $arrAdmediumItems = $arrResponse['admediumItems']['admediumItem'];
        $arrResult = array();
        foreach($arrAdmediumItems as $admediumItems) {
            $Deal = Deal::createInstance();
            $Deal->deal_ID = (int)$admediumItems['@id'];
            $Deal->name = $admediumItems['name'];
            $Deal->deal_type = $admediumItems['admediumType'];
            $Deal->merchant_ID = (int)$admediumItems['program']['@id'];
            $Deal->ppv = $admediumItems['trackingLinks']['trackingLink'][0]['ppv'];
            $Deal->ppc = $admediumItems['trackingLinks']['trackingLink'][0]['ppc'];
            if($merchantID > 0) {
                if($merchantID == $admediumItems['program']['@id']) {
                    $arrResult[] = $Deal;
                }
            }
            else {
                $arrResult[] = $Deal;
            }
        }

        return $arrResult;
    }



    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array
    {
        if (!$this->checkLogin()){
            return array();
        }
        $arrResult = array();
        if (count($arrMerchantID)<1){
            $merchants=$this->getMerchants();
            foreach ($merchants as $merchant){
                $arrMerchantID[$merchant->merchant_ID]=['cid'=>$merchant->merchant_ID,'name'=>$merchant->name];
            }
        }
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateTo, $dateFrom);
        foreach($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            array_key_exists_safe($transaction,'currency')?$Transaction->currency = $transaction['currency']:$Transaction->currency = '';
            array_key_exists_safe($transaction,'status')?$Transaction->status = $transaction['status']:$Transaction->status = '';
            array_key_exists_safe($transaction,'amount')?$Transaction->amount = $transaction['amount']:$Transaction->amount = '';
            array_key_exists_safe($transaction,'custom_id')?$Transaction->custom_ID = $transaction['custom_id']:$Transaction->custom_ID = '';
            array_key_exists_safe($transaction,'title')? $Transaction->title = $transaction['title']:$Transaction->title = '';
            array_key_exists_safe($transaction,'unique_id')?$Transaction->unique_ID = $transaction['unique_id']:$Transaction->unique_ID = '';
            array_key_exists_safe($transaction,'commission')?$Transaction->commission = $transaction['commission']:$Transaction->commission = '';
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            array_key_exists_safe($transaction,'merchantId')?$Transaction->merchant_ID = $transaction['merchantId']:$Transaction->merchant_ID = '';
            array_key_exists_safe($transaction,'approved')?$Transaction->approved = $transaction['approved']:$Transaction->approved = '';
            $arrResult[] = $Transaction;
        }

        return $arrResult;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Stat
     */
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array
    {
        return array();
        /*
        $this->_apiClient->setConnectId($this->_username);
        $this->_apiClient->setSecretKey($this->_password);
        $dateFromIsoEngFormat = $dateFrom->format('Y-m-d');
        $dateToIsoEngFormat = $dateTo->format('Y-m-d');
        $response = $this->_apiClient->getReportBasic($dateFromIsoEngFormat, $dateToIsoEngFormat);
        $arrResponse = json_decode($response, true);
        $reportItems = $arrResponse['reportItems'];
        $Stat = Stat::createInstance();
        $Stat->reportItems = $reportItems;

        return array($Stat);
        */
    }
}
