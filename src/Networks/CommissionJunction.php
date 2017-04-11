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
    protected $_tracking_parameter = 'sid';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $passwordApi, string $idSite='')
    {
        $this->_network = new \Oara\Network\Publisher\CommissionJunction;
        $this->_username = $username;
        $this->_password = $passwordApi;
        $this->_passwordApi = $passwordApi;
        $this->_website_id = $idSite;
        $this->login( $this->_username, $this->_password ,$idSite);
        // $this->_apiClient = \ApiClient::factory(PROTOCOL_JSON);
    }

    /**
     * @return bool
     */
    public function login(string $username, string $password,string $idSite=''): bool
    {
        $this->_logged = false;
        if (isNullOrEmpty( $username ) && isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $this->_passwordApi= $password;
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["apipassword"] = $this->_passwordApi;
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
    public function getMerchants(): array
    {
        $arrResult = array();
        $merchantList = $this->_network->getMerchantList();
        foreach ($merchantList as $merchant) {
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
    public function getDeals($merchantID = NULL, int $page = 0, int $items_per_page = 10): DealsResultset
    {
        if ($page<1){
            $page=1;
        }
        $arrResult = new DealsResultset();
        $response = $this->_apiCall('https://link-search.api.cj.com/v2/link-search?website-id=' . $this->_website_id . '&promotion-type=coupon&advertiser-ids=joined&records-per-page='.$items_per_page.'&page-number='.$page);
        //var_dump($response);
        if ($response===false || \preg_match("/error/", $response)) {
            return $arrResult;
        }


        $arrResponse = xml2array($response);

        if (!is_array($arrResponse) || count($arrResponse) <= 0) {
            return $arrResult;
        }
        if (!isset($arrResponse['cj-api']['links'])){
            return $arrResult;
        }
        $arrResult->page=$arrResponse['cj-api']['links_attr']['page-number'];
        $arrResult->items=$arrResponse['cj-api']['links_attr']['records-returned'];
        $arrResult->total=$arrResponse['cj-api']['links_attr']['total-matched'];
        ($arrResult->total>0)?$arrResult->num_pages=(int)ceil($arrResult->total/$items_per_page):$arrResult->num_pages=0;
        $arrCoupon = $arrResponse['cj-api']['links']['link'];

        foreach ($arrCoupon as $coupon) {
            //  var_dump($coupon);
            $Deal = Deal::createInstance();
            $Deal->id = $coupon['link-id'];
            $Deal->name = $coupon['link-name'];
            $Deal->description = $coupon['description'];
            $Deal->note = $coupon['description'];
            $Deal->merchant_ID = $coupon['advertiser-id'];
            $Deal->merchant_name = $coupon['advertiser-name'];
            $Deal->ppc = $coupon['clickUrl'];
            $Deal->description = $coupon['description'];
            $startDate = new \DateTime($coupon['promotion-start-date']);
            $Deal->startDate = $startDate;
            $Deal->created_at = $startDate;
            $endDate = new \DateTime($coupon['promotion-end-date']);
            $Deal->endDate = $endDate;
            $Deal->code = $coupon['coupon-code'];
            if ($merchantID > 0) {
                if ($merchantID == $coupon['advertiser-id']) {
                    $arrResult->deals[] = $Deal;
                }
            } else {
                $arrResult->deals[] = $Deal;
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
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()): array
    {
        $arrResult = array();
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom,$dateTo);
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
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0): array
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

    /**
     * Api call CommissionJunction
     */
    private function _apiCall($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " . $this->_passwordApi));
        $curl_results = curl_exec($ch);
        curl_close($ch);
        return $curl_results;
    }

    public function getTrackingParameter()
    {
        return $this->_tracking_parameter;
    }
}
