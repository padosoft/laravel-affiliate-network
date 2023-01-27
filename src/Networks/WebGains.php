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
    private $_idSite = '';
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
        $this->_idSite = $idSite;
        $apiUrl = 'http://ws.webgains.com/aws.php';
        $this->_apiClient = new \SoapClient($apiUrl,
            array('login' => $this->_username,
                'encoding' => 'UTF-8',
                'password' => $this->_password,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
                'soap_version' => SOAP_1_1)
        );
        $this->login( $this->_username, $this->_password, $this->_idSite);
    }

    public function login(string $username, string $password, string $idSite = ''): bool{
        $this->_logged = false;
        if (isNullOrEmpty( $username ) || isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $this->_idSite = $idSite;
        $credentials = array();
        $credentials["sitesAllowed"] = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
        if (!empty($this->_idSite)){
            $credentials["sitesAllowed"] = explode(",", $this->_idSite);
        }
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
            // Added more info - 2018-04-23 <PN>
            $Merchant->status = $merchant['status'];
            $Merchant->url = $merchant['url'];
            $arrResult[] = $Merchant;
        }

        return $arrResult;
    }

    /**
     * @param int $merchantID to filter only one merchant
     * @return array of Deal
     */
    public function getDeals($merchantID = null, int $page = 0, int $items_per_page = 10): DealsResultset
    {

        $result = DealsResultset::createInstance();
        $arrResult = array();
        if (!empty($this->_idSite)) {
            $a_sites = explode(",", $this->_idSite);
            foreach ($a_sites as $id_site){
                // Account id is correct
                $arrVouchers = $this->_network->getVouchers($id_site);

                foreach ($arrVouchers as $obj_voucher) {

                    $voucher = str_getcsv($obj_voucher, ',', '"');

                    if (count($voucher) < 12) {
                        continue;
                    }
                    $voucher_id = $voucher[0];
                    $voucher_id = str_replace("\n", '', $voucher_id);
                    $advertiserId = $voucher[1];
                    $code = $voucher[7];
                    if ($voucher_id == "Voucher ID" || !is_numeric($advertiserId) || $code == "Code") {
                        continue;
                    }
                    $starts = $voucher[4];
                    $ends = $voucher[5];
                    $deeplink_tracking = $voucher[6];
                    $description = $voucher[11];
                    $discount = abs((int)$voucher[8]);
                    $is_percentage = (bool)(strpos($voucher[8], '%') !== false);

                    if ($merchantID > 0) {
                        if ($advertiserId != $merchantID) {
                            continue;
                        }
                    }

                    $Deal = Deal::createInstance();
                    $Deal->deal_ID = $voucher_id;
                    $Deal->merchant_ID = $advertiserId;
                    $Deal->code = $code;
                    $Deal->description = $description;
                    $Deal->start_date = $Deal->convertDate($starts . ' 00:00:00');
                    $Deal->end_date = $Deal->convertDate($ends . ' 23:59:59');
                    $Deal->default_track_uri = $deeplink_tracking;
                    $Deal->is_exclusive = false;
                    $Deal->discount_amount = $discount;
                    $Deal->is_percentage = $is_percentage;
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;
                    $arrResult[] = $Deal;
                }
            }

        }
        $result->deals[]=$arrResult;
        return $result;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array $arrMerchantID
     * @return array of Transaction
     * @throws \Exception
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array
    {
        /*
    	if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        */
        $arrResult = array();
        $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
        foreach($transactionList as $transaction) {
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
            if ($transaction['paid'] == true) {
                $Transaction->paid = true;
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
