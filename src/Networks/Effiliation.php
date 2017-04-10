<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\ProductsResultset;

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
    protected $_tracking_parameter    = 'effi_id';
    /**
     * @method __construct
     */
    public function __construct(string $username, string $password,string $idSite='')
    {
        $this->_network = new \Padosoft\AffiliateNetwork\EffiliationEx;
        $this->_username = $username;
        $this->_password = $password;
        $this->login( $this->_username, $this->_password );
        $this->_apiClient = null;
    }
    public function login(string $username, string $password,string $idSite=''): bool{
        $this->_logged = false;
        if (isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $credentials = array();
        $credentials["apiPassword"] = $this->_password;
        $this->_network->login($credentials);
        if ($this->_network->checkConnection()) {
            $this->_logged = true;

        }

        return $this->_logged;
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
    public function getDeals($merchantID=NULL,int $page=0,int $items_per_page=10 ): DealsResultset
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
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
        foreach($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->merchant_ID = $transaction['merchantId'];
            $Transaction->title ='';
            $Transaction->currency ='EUR';
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            $Transaction->unique_ID = $transaction['unique_id'];
            $Transaction->custom_ID = $transaction['custom_id'];
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->commission = $transaction['commission'];
            $Transaction->approved = false;
            if ($transaction['status'] == \Oara\Utilities::STATUS_CONFIRMED){
                $Transaction->approved = true;
            }
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


    /**
     * @param  array $params
     *
     * @return ProductsResultset
     */
    public function getProducts(array $params = []): ProductsResultset
    {
        // TODO: Implement getProducts() method.
        throw new \Exception("Not implemented yet");
    }

    public function getTrackingParameter(){
        return $this->_tracking_parameter;
    }
}
