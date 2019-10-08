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
use Padosoft\AffiliateNetwork\AffilinetEx;

/**
 * Class Affilinet
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Affilinet extends AbstractNetwork implements NetworkInterface
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
    protected $_tracking_parameter = 'subid';   // Default value

    /**
     * @method __construct
     */
    public function __construct(string $username, string $passwordApi, string $idSite='')
    {
        $this->_network = new AffilinetEx;
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
        $credentials["password"] = $this->_username;
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
            // Added more info - 2018-04-20 <PN>
            $Merchant->url = $merchant['url'];
            $Merchant->status = $merchant['status'];
            if (!empty($merchant['launch_date'])) {
                $date = new \DateTime($merchant['launch_date']);
                //TODO check date format
                //$Merchant->launch_date = $date;
            }
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
        $result = DealsResultset::createInstance();

        $arrResult = array();
        $arrVouchers = $this->_network->getVouchers();

        foreach($arrVouchers as $obj_voucher) {

            $voucher = json_decode(json_encode($obj_voucher), true);

            if ($merchantID > 0) {
                if ($voucher['ProgramId'] != $merchantID) {
                    continue;
                }
            }

            $partnershipStatus = $voucher['PartnershipStatus'];
            if ($partnershipStatus == 'NoRestriction' || $partnershipStatus == 'Accepted') {
                // Get only approved or without restriction
                $Deal = Deal::createInstance();
                $Deal->setValues($voucher, [
                    'Id' => 'deal_ID',
                    'ProgramId' => 'merchant_ID',
                    'Code' => 'code',
                    'LastChengeDate' => 'update_date',
                    'StartDate' => 'start_date',
                    'EndDate' => 'end_date',
                    'Title' => 'name',
                    'Description' => 'description',
                    'IsExclusive' => 'is_exclusive',
                    'MinimumOrderValue' => 'minimum_order_value',
                ]);
                /*
                // Check voucher type (NOT USED HERE)
                $voucherType = $voucher['VoucherTypes']['VoucherType'];
                if (is_array($voucherType)) {
                    foreach ($voucherType as $type) {
                        switch ($type) {
                            case 'AllProducts':
                                break;
                            case 'SpecificProducts':
                                break;
                            case 'MultiBuyDiscount':
                                break;
                            case 'Free Shipping':
                                break;
                            case 'Free Product':
                                break;
                            case 'Competition':
                                break;
                        }
                    }
                }
                */
                $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;

                // Decode tracking url from html snippet
                $snippet = $voucher['IntegrationCode'];
                if (!empty($snippet)) {
                    $pos = strpos($snippet, 'http');
                    if ($pos !== false) {
                        $posQuote = strpos($snippet,'"', $pos);
                        if ($posQuote === false) {
                            $posQuote = strpos($snippet,' ', $pos);
                        }
                        if ($posQuote !== false) {
                            $Deal->default_track_uri = substr($snippet, $pos, $posQuote - $pos);
                        }
                    }
                }
                $arrResult[] = $Deal;
            }
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
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()): array
    {
        $arrResult = array();
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom,$dateTo);
        //echo "<br>merchants id array<br>".print_r($arrMerchantID);
        //$counter=0;
        foreach($transactionList as $transaction) {
            $myTransaction = Transaction::createInstance();
            try {
                $myTransaction->status = $transaction['status'];
                $myTransaction->amount = $transaction['amount'];
                $myTransaction->custom_ID = $transaction['custom_id'];
                $myTransaction->unique_ID = $transaction['unique_id'];
                $myTransaction->commission = $transaction['commission'];
                $myTransaction->currency = $transaction['currency'];
                if (!empty($transaction['date'])) {
                    $date = new \DateTime($transaction['date']);
                    $myTransaction->date = $date; // $date->format('Y-m-d H:i:s');
                }
                $myTransaction->merchant_ID = $transaction['merchantId'];
                // Future use - Only few providers returns these dates values - <PN> - 2017-06-26
                if (isset($transaction['click_date']) && !empty($transaction['click_date'])) {
                    $myTransaction->click_date = new \DateTime($transaction['click_date']);
                }
                if (isset($transaction['update_date']) && !empty($transaction['update_date'])) {
                    $myTransaction->update_date = new \DateTime($transaction['update_date']);
                }
                $arrResult[] = $myTransaction;
            } catch (\Exception $e) {
                //echo "stepE ";
                echo "<br><br>errore transazione Affilinet, id: ".$myTransaction->unique_ID." msg: ".$e->getMessage()."<br><br>";
                var_dump($e->getTraceAsString());
            }
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
