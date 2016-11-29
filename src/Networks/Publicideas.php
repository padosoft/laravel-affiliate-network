<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/Publicideas/Zapi/ApiClient.php";

/**
 * Class Publicideas
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Publicideas extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_username = '';
    private $_password = '';
    private $_token = '';
    private $_partner_id = '';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password, string $token, string $partner_id)
    {
        $this->_network = new \Oara\Network\Publisher\Publicidees;
        $this->_username = $username;
        $this->_password = $password;
        $this->_token = $token;
        $this->_partner_id = $partner_id;
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

        var_dump($this->_network->checkConnection());

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
        $url = 'http://publisher.publicideas.com/xmlProgAff.php?partid='.$this->_partner_id.'&key='.$this->_token.'&noDownload=yes';
        $xml = file_get_contents($url);
        $arrResult = array();
        $arrResponse = xml2array($xml);
        if(!is_array($arrResponse) || count($arrResponse) <= 0) {
            return $arrResult;
        }
        $arrPartner = $arrResponse['partner'];
        echo '<pre>';
        var_dump($arrPartner);
        echo '</pre>';

        /*
        foreach($arrPartner as $partner) {
            $Deal = Deal::createInstance();
            $Deal->program_name;
            if($merchantID > 0) {
                if($merchantID == $admediumItems['program']['@id']) {
                    $arrResult[] = $Deal;
                }
            }
            else {
                $arrResult[] = $Deal;
            }
        }
        */



        /*
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
        */

       return array();

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
