<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\NetAffiliationEx;
use Padosoft\AffiliateNetwork\ProductsResultset;

if (!defined('COOKIES_BASE_DIR')){
    define('COOKIES_BASE_DIR',public_path('upload/report'));
}
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
    private $_idSite = '';
    private $_logged    = false;
    protected $_tracking_parameter    = 'argsite';
    protected $_merchants = array();    // To avoid repeated calls to getMerchants()

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password,string $idSite='')
    {
        $this->_network = new NetAffiliationEx();
        $this->_username = $username;
        $this->_password = $password;
        if (trim($idSite)!=''){
            $this->addAllowedSite($idSite);
        }
        $this->login( $this->_username, $this->_password );
    }

    public function addAllowedSite($idSite){
        if (trim($idSite)!=''){
            $this->_network->addAllowedSite($idSite);
        }
    }

    public function login(string $username, string $password,string $idSite=''): bool
    {
        if (trim($idSite)!=''){
            $this->addAllowedSite($idSite);
        }
        $this->_logged = false;
        if (isNullOrEmpty( $username ) || isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
        $credentials["apiPassword"] = $this->_password;
        $this->_network->login( $credentials );
        //$this->_apiClient = $this->_network->getApiClient();
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
        if (!$this->checkLogin()) {
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
     * @param int|null $merchantID
     * @param int $page
     * @param int $items_per_page
     *
     * @return DealsResultset
     */
    public function getDeals($merchantID=NULL,int $page=0,int $items_per_page=10 ): DealsResultset
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
        try {
            if (!$this->checkLogin()) {
                return array();
            }
            $arrResult = array();
            if (count( $arrMerchantID ) < 1) {
                if (count($this->_merchants) == 0) {
                    $this->_merchants = $this->getMerchants();
                }
                foreach ($this->_merchants as $merchant) {
                    $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
                }
            }

            $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
            foreach($transcationList as $transaction) {
                try {
                    $Transaction = Transaction::createInstance();
                    array_key_exists_safe( $transaction,
                        'currency' ) ? $Transaction->currency = $transaction['currency'] : $Transaction->currency = '';
                    array_key_exists_safe( $transaction,
                        'status' ) ? $Transaction->status = $transaction['status'] : $Transaction->status = '';
                    array_key_exists_safe( $transaction,
                        'amount' ) ? $Transaction->amount = $transaction['amount'] : $Transaction->amount = '';
                    array_key_exists_safe( $transaction,
                        'custom_id' ) ? $Transaction->custom_ID = $transaction['custom_id'] : $Transaction->custom_ID = '';
                    array_key_exists_safe( $transaction,
                        'title' ) ? $Transaction->title = $transaction['title'] : $Transaction->title = '';
                    array_key_exists_safe( $transaction,
                        'unique_id' ) ? $Transaction->unique_ID = $transaction['unique_id'] : $Transaction->unique_ID = '';
                    array_key_exists_safe( $transaction,
                        'commission' ) ? $Transaction->commission = $transaction['commission'] : $Transaction->commission = 0;
                    $date = new \DateTime( $transaction['date'] );
                    $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
                    array_key_exists_safe( $transaction,
                        'merchantId' ) ? $Transaction->merchant_ID = $transaction['merchantId'] : $Transaction->merchant_ID = '';
                    array_key_exists_safe( $transaction,
                        'approved' ) ? $Transaction->approved = $transaction['approved'] : $Transaction->approved = '';
                    $arrResult[] = $Transaction;
                } catch (\Exception $e) {
                    //echo "stepE ";
                    echo "<br><br>errore transazione NetAffiliation, id: ".$transaction->unique_ID." msg: ".$e->getMessage()."<br><br>";
                    //var_dump($e->getTraceAsString());
                }

            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
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
