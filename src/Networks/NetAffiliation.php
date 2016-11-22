<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;

/**
 * Class NetAffiliation
 * @package Padosoft\AffiliateNetwork\Networks
 */
class NetAffiliation extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_username = '';
    private $_password = '';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password)
    {
        $this->_network = new \Oara\Network\Publisher\NetAffiliation;
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
    {
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
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
        $url = 'http://flux.netaffiliation.com/rsscp.php?sec=417771E811773642F4E017';
        $xml = file_get_contents($url);
        $arrResult = array();
        $arrResponse = xml2array($xml);
        if(!is_array($arrResponse) || count($arrResponse) <= 0) {
            return $arrResult;
        }

        $arrItems = $arrResponse['rss']['channel']['item'];
        foreach($arrItems as $item) {
            $Deal = Deal::createInstance();
            $Deal->merchant_ID = $item['idcamp'];
            $Deal->code = $item['code'];
            $Deal->name = $item['title'];
            $Deal->startDate = $item['startdate'];
            $Deal->endDate = $item['enddate'];
            $Deal->description = $item['description'];
            $Deal->url = $item['link'];
            if($merchantID > 0) {
                if($merchantID == $item['idcamp']) {
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
        $arrResult = array();
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateTo, $dateFrom);
        foreach($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->custom_ID = $transaction['custom_id'];
            $Transaction->title = $transaction['title'];
            $Transaction->commission = $transaction['commission'];
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            $Transaction->merchant_ID = $transaction['merchantId'];
            $Transaction->approved = $transaction['approved'];
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
