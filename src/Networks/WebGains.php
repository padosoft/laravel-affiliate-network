<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\ProductsResultset;

/**
 * Class WebGains
 * @package Padosoft\AffiliateNetwork\Networks
 */
class WebGains extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_username = '';
    private $_password = '';
    private $_apiClient = null;
    protected $_tracking_parameter    = 'clickref';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password,string $idSite='')
    {
        $this->_network = new \Oara\Network\Publisher\WebGains;
        $this->_username = $username;
        $this->_password = $password;
        $apiUrl = 'http://ws.webgains.com/aws.php';
        $this->_apiClient = new \SoapClient($apiUrl,
            array('login' => $this->_username,
                'encoding' => 'UTF-8',
                'password' => $this->_password,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
                'soap_version' => SOAP_1_1)
        );
        $this->login( $this->_username, $this->_password );
    }

    public function login(string $username, string $password,string $idSite=''): bool{
        $this->_logged = false;
        if (isNullOrEmpty( $username ) || isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
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
    public function getDeals($merchantID=NULL,int $page=0,int $items_per_page=10 ): DealsResultset
    {
        $arrResult = array();
        $arrResponse = $this->_apiClient->getFullEarnings(null, null, null, $this->_username, $this->_password);
        foreach($arrResponse as $response) {
            $Deal = Deal::createInstance();
            $Deal->transaction_ID = $response->transactionID;
            $Deal->affiliate_ID = $response->affiliate_ID;
            $Deal->campaign_name = $response->campaignName;
            $Deal->campaign_ID = $response->campaignID;
            $date = new \DateTime($response->date);
            $Deal->date = $response->date;
            $Deal->programName = $response->program_name;
            $Deal->merchant_ID = $response->programID;
            $Deal->commission = $response->commission;
            $Deal->amount = $response->saleValue;
            $Deal->status = $response->status;
            $Deal->referrer = $response->referrer;
            if($merchantID > 0) {
                if($merchantID == $response->programID) {
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
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        $arrResult = array();
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
        foreach($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->currency = $transaction['currency'];
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            array_key_exists_safe( $transaction,
                'custom_id' ) ? $Transaction->custom_ID = $transaction['custom_id'] : $Transaction->custom_ID = '';
            $Transaction->title = '';
            $Transaction->unique_ID = $transaction['unique_id'];
            $Transaction->commission = $transaction['commission'];
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            $Transaction->merchant_ID = $transaction['merchantId'];
            $Transaction->approved = false;
            if ($Transaction->status==\Oara\Utilities::STATUS_CONFIRMED){
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
     * @param array|null $merchantID
     * @param int $page
     * @param int $pageSize
     *
     * @return ProductsResultset
     */
    public function getProducts(array $merchantID = null, int $page, int $pageSize): ProductsResultset
    {
        // TODO: Implement getProducts() method.
        throw new \Exception("Not implemented yet");
    }

    public function getTrackingParameter(){
        return $this->_tracking_parameter;
    }
}
