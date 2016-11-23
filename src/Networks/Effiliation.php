<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/Effiliation/Zapi/ApiClient.php";

/**
 * Class Effiliation
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Effiliation extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_apiClient = null;
    private $_password = '';

    /**
     * @method __construct
     */
    public function __construct(string $password)
    {
        $this->_network = new \Oara\Network\Publisher\Effiliation;
        $this->_password = $password;
        $this->_apiClient = null;
    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
    {
        $credentials = array();
        $credentials["apipassword"] = $this->_password;
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
        $url = 'http://api.effiliation.com/apiv2/programs.xml?key=' . $this->_password . "&filter=all";
        $content = @\file_get_contents($url);
        $xml = \simplexml_load_string($content, null, LIBXML_NOERROR | LIBXML_NOWARNING);
        foreach ($xml->program as $merchant) {
            $Merchant = Merchant::createInstance();
            $Merchant->merchant_ID = (string)$merchant->id_programme;
            $Merchant->name = (string)$merchant->nom;
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
        $url = 'http://apiv2.effiliation.com/apiv2/programs.json?filter=mines&key='.$this->_password;
        $json = file_get_contents($url);
        $arrResult = array();
        $arrResponse = json_decode($json, true);
        if(!is_array($arrResponse) || count($arrResponse) <= 0) {
            return $arrResult;
        }
        $arrPrograms = $arrResponse['programs'];
        foreach($arrPrograms as $item) {
            $Deal = Deal::createInstance();
            $Deal->merchant_ID = $item['id_programme'];
            $Deal->code = $item['code'];
            $Deal->name = $item['nom'];
            $Deal->startDate = $item['date_debut'];
            $Deal->description = $item['description'];
            $Deal->url = $item['url_tracke'];
            if($merchantID > 0) {
                if($merchantID == $item['id_programme']) {
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
            $Transaction->merchant_ID = $transaction['merchantId'];
            $date = new \DateTime($transaction['date']);
            $Transaction->unique_ID = $transaction['unique_id'];
            $Transaction->custom_ID = $transaction['custom_id'];
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->commission = $transaction['commission'];
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
