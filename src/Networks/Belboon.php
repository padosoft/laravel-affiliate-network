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

/**
 * Class Belboon
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Belboon extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_apiClient = null;
    private $_password = '';
    private $_idSite = '';
    protected $_tracking_parameter    = '/subid1';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password,string $idSite='')
    {
        $this->_network = new \Padosoft\AffiliateNetwork\BelboonEx;
        $this->_username = $username;
        $this->_password = $password;
        $this->login( $this->_username, $this->_password, $idSite );
        $this->_apiClient = null;
    }

    public function login(string $username, string $password, string $idSite=''): bool{
        $this->_logged = false;
        if (isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $this->_idSite = $idSite;
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["apipassword"] = $this->_password;
        $credentials['id_site'] = $idSite;
        if ($this->_network->login($credentials)) {
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
            $Merchant->status = $merchant['status'];
            $Merchant->url = $merchant['url'];
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
        $result = DealsResultset::createInstance();

        if (!isset($_ENV['BELBOON_API_VOUCHER_KEY'])) {
            throw new \Exception("Belboon api key not defined");
        }
        $apiKey = $_ENV['BELBOON_API_VOUCHER_KEY'];

        $arrResult = array();


        $vouchers = array();

        try {
            $params = array(
                new \Oara\Curl\Parameter('key', $apiKey),
                new \Oara\Curl\Parameter('platformid', $this->_idSite),
                new \Oara\Curl\Parameter('status', 'all'),
                new \Oara\Curl\Parameter('format', 'csv'),
            );

            $credentials = [];
            $exportClient = new \Oara\Curl\Access($credentials);


            $urls[] = new \Oara\Curl\Request('https://ui.belboon.com/export/vouchercodes/?', $params);
            $result = $exportClient->get($urls);
            if ($result === false || !is_array($result))
            {
                throw new \Exception("Belboon getVouchers - http error");
            } else {
                $vouchers = \str_getcsv($result[0], "\n");
            }
        } catch (\Exception $e) {
            echo "Belboon getVouchers error:".$e->getMessage()."\n ";
            throw new \Exception($e);
        }

        foreach($vouchers as $obj_voucher) {

            $voucher = str_getcsv($obj_voucher, ';', '"');

            $promotionId = $voucher[2];
            if (!is_numeric($promotionId)) {
                continue;
            }
            $advertiser = $voucher[2];
            $advertiserId = $voucher[2];
            $type = $voucher[4];
            $code = $voucher[5];
            $description = $voucher[9];
            $starts = $voucher[7];
            $ends = $voucher[8];
            $deeplink_tracking = $voucher[10];
            $exclusive = $voucher[0];

            if ($merchantID > 0) {
                if ($advertiserId != $merchantID) {
                    continue;
                }
            }

            $Deal = Deal::createInstance();
            $Deal->deal_ID = $promotionId;
            $Deal->merchant_ID = $advertiserId;
            $Deal->code = $code;
            $Deal->description = $description;
            $Deal->start_date = $Deal->convertDate($starts);
            $Deal->end_date = $Deal->convertDate($ends);
            $Deal->default_track_uri = $deeplink_tracking;
            $Deal->is_exclusive = $exclusive;
            $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;
            $arrResult[] = $Deal;
        }
        $result->deals[]=$arrResult;

        return $result;
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
        try {
            if (count( $arrMerchantID ) < 1) {
                $merchants = $this->getMerchants();
                foreach ($merchants as $merchant) {
                    $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
                }
            }
             $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);

            foreach($transactionList as $transaction) {
                $myTransaction = Transaction::createInstance();
                try {
                    $myTransaction->merchant_ID = $transaction['merchantId'];
                    $myTransaction->title ='';
                    $myTransaction->currency ='EUR';
                    if (!empty($transaction['date'])) {
                        $date = new \DateTime($transaction['date']);
                        $myTransaction->date = $date; // $date->format('Y-m-d H:i:s');
                    }
                    if (!empty($transaction['lastchangedate'])) {
                        $date = new \DateTime($transaction['lastchangedate']);
                        $myTransaction->update_date = $date;
                    }
                    $myTransaction->unique_ID = $transaction['unique_id'];
                    $myTransaction->custom_ID = array_key_exists('custom_id', $transaction) ? $transaction['custom_id'] : '';
                    $myTransaction->status = $transaction['status'];
                    $myTransaction->amount = $transaction['amount'];
                    $myTransaction->commission = $transaction['commission'];
                    $myTransaction->approved = false;
                    if ($transaction['status'] == \Oara\Utilities::STATUS_CONFIRMED){
                        $myTransaction->approved = true;
                    }
                    $arrResult[] = $myTransaction;
                } catch (\Exception $e) {
                    echo "<br><br>errore transazione Belboon, id: ".$myTransaction->unique_ID." msg: ".$e->getMessage()."<br><br>";
                    var_dump($e->getTraceAsString());
                }
            }
        } catch (\Exception $e) {
            echo "<br><br>errore generico transazione Belboon: ".$e->getMessage()."<br><br>";
            var_dump($e->getTraceAsString());
            throw new \Exception($e);
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
