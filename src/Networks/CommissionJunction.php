<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\DealsResultset;

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/CommissionJunction/Zapi/ApiClient.php";

/**
 * Class CommissionJunction
 * @package Padosoft\AffiliateNetwork\Networks
 */
class CommissionJunction extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    // private $_apiClient = null;
    private $_username = '';
    private $_password = '';
    private $_passwordApi = '';
    private $_website_id = '';
    protected $_tracking_parameter    = 'sid';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password, string $passwordApi, string $website_id)
    {
        $this->_network = new \Oara\Network\Publisher\CommissionJunction;
        $this->_username = $username;
        $this->_password = $password;
        $this->_passwordApi = $passwordApi;
        $this->_website_id = $website_id;
        // $this->_apiClient = \ApiClient::factory(PROTOCOL_JSON);
    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
    {
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
        $credentials["apipassword"] = $this->_passwordApi;

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
    public function getDeals($merchantID=NULL,int $page=0,int $items_per_page=10 ): DealsResultset
    {
        $response = $this->_apiCall('https://link-search.api.cj.com/v2/link-search?website-id='.$this->_website_id.'&promotion-type=coupon&advertiser-ids=joined');
        if (\preg_match("/error/", $response)) {
            return false;
        }
        $arrResult = array();
        $arrResponse = xml2array($response);
        if(!is_array($arrResponse) || count($arrResponse) <= 0) {
            return $arrResult;
        }
        $arrCoupon = $arrResponse['cj-api']['links']['link'];
        foreach($arrCoupon as $coupon) {
            $Deal = Deal::createInstance();
            $Deal->merchant_ID = $coupon['advertiser-id'];
            $Deal->merchant_name = $coupon['advertiser-name'];
            $Deal->ppc = $coupon['click-commission'];
            $Deal->description = $coupon['description'];
            $startDate = new \DateTime($coupon['promotion-start-date']);
            $Deal->startDate = $startDate;
            $endDate = new \DateTime($coupon['promotion-end-date']);
            $Deal->endDate = $endDate;
            $Deal->code = $coupon['coupon-code'];
            if($merchantID > 0) {
                if($merchantID == $coupon['advertiser-id']) {
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
            $Transaction->unique_ID = $transaction['unique_id'];
            $Transaction->commission = $transaction['commission'];
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            $Transaction->merchant_ID = $transaction['merchantId'];
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
    * Api call CommissionJunction
    */
    private function _apiCall($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " . $this->_passwordApi));
        $curl_results = curl_exec($ch);
        curl_close($ch);
        return $curl_results;
    }

    public function getTrackingParameter(){
        return $this->_tracking_parameter;
    }
}
