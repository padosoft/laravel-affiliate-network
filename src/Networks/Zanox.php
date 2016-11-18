<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
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

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password)
    {
        $this->_network = new \Oara\Network\Publisher\Zanox;
        $this->_username = $username;
        $this->_password = $password;
        $this->_apiClient = \ApiClient::factory(PROTOCOL_JSON);
    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
    {
        $credentials = array();
        $credentials["connectid"] = $this->_username;
        $credentials["secretkey"] = $this->_password;
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
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array
    {
        $arrResult = array();
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateTo, $dateFrom);
        foreach($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->currency = $transaction['currency'];
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->custom_ID = $transaction['custom_id'];
            $Transaction->title = $transaction['title'];
            $Transaction->unique_ID = $transaction['unique_id'];
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
        $this->_apiClient->setConnectId($this->_username);
        $this->_apiClient->setSecretKey($this->_password);
        $dateFromIsoEngFormat = $dateFrom->format('Y-m-d');
        $dateToIsoEngFormat = $dateTo->format('Y-m-d');
        $response = $this->_apiClient->getReportBasic($dateFromIsoEngFormat, $dateToIsoEngFormat);

        return json_decode($response, true);
    }
}
