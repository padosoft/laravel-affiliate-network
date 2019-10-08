<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\TradeDoublerEx;
use Padosoft\AffiliateNetwork\ProductsResultset;

if (!defined('COOKIES_BASE_DIR')){
    define('COOKIES_BASE_DIR',public_path('upload/report'));
}
/**
 * Class TradeDoubler
 * @package Padosoft\AffiliateNetwork\Networks
 */
class TradeDoubler extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_apiClient = null;
    private $_username = '';
    private $_password = '';
    private $_idSite = '';
    private $_logged    = false;
    protected $_tracking_parameter    = 'epi';

    /**
	 * TradeDoubler constructor.
	 * @param string $username
	 * @param string $password
	 * @param string $idSite
	 */
    public function __construct(string $username, string $password, string $idSite = '')
    {
        $this->_network = new \Oara\Network\Publisher\TradeDoubler;
        $this->_username = $username;
        $this->_password = $password;
	    $this->_idSite = $idSite;
        $this->_apiClient = null;
        $this->login( $this->_username, $this->_password, $this->_idSite );
    }

    public function login(string $username, string $password, string $idSite = ''): bool
    {
        $this->_logged = false;
        if (isNullOrEmpty( $username ) || isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $this->_idSite = $idSite;
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
        $credentials["idSite"] = $this->_idSite;
        $this->_network->login( $credentials );

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
            $Merchant->status = $merchant['status'];
            if (!empty($merchant['launch_date'])) {
                $date = new \DateTime($merchant['launch_date']);
                $Merchant->launch_date = $date;
            }
            if (!empty($merchant['application_date'])) {
                $date = new \DateTime($merchant['application_date']);
                $Merchant->application_date = $date;
            }
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
    public function getDeals($merchantID,int $page=0,int $items_per_page=10) : DealsResultset
    {
        if (!isIntegerPositive($items_per_page)){
            $items_per_page=10;
        }
        $result=DealsResultset::createInstance();
        if (!$this->checkLogin()) {
            return $result;
        }
        $arrResult = array();
        $jsonVouchers = file_get_contents("https://api.tradedoubler.com/1.0/vouchers.json;dateOutputFormat=iso8601?token=".$_ENV['TRADEDOUBLER_TOKEN']);
        $arrVouchers = json_decode($jsonVouchers, true);

        foreach($arrVouchers as $voucher) {
            $Deal = Deal::createInstance();
            $Deal->setValues($voucher, [
                'id' => 'deal_ID' ,
                'programId' => 'merchant_ID' ,
                'code' => 'code' ,
                'updateDate' => 'update_date' ,
                'publishStartDate' => 'publish_start_date' ,
                'publishEndDate' => 'publish_end_date' ,
                'startDate' => 'start_date' ,
                'endDate' => 'end_date' ,
                'title' => 'name' ,
                'shortDescription' => 'short_description' ,
                'description' => 'description' ,
                'voucherTypeId' => 'deal_type' ,
                'defaultTrackUri' => 'default_track_uri' ,
                'landingUrl' => 'landing_url' ,
                'discountAmount' => 'discount_amount' ,
                'isPercentage' => 'is_percentage' ,
                'publisherInformation' => 'information' ,
                'languageId' => 'language' ,
                'exclusive' => 'is_exclusive' ,
                'siteSpecific' => 'is_site_specific' ,
                'currencyId' => 'currency_initial' ,
                'logoPath' => 'logo_path' ,
            ]);
            switch ($voucher['voucherTypeId']) {
                case 1:
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;
                    break;
                case 2:
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_DISCOUNT;
                    break;
                case 3:
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_FREE_ARTICLE;
                    break;
                case 4:
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_FREE_SHIPPING;
                    break;
                case 5:
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_LOTTERY;
                    break;
            }

            if($merchantID > 0) {
                if($voucher['programId'] == $merchantID) {
                    $arrResult[] = $Deal;
                }
            }
            else {
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
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array
    {
        if (!$this->checkLogin()) {
            return array();
        }
        $arrResult = array();
        $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
        foreach($transactionList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->merchant_ID = $transaction['merchantId'];
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            $Transaction->unique_ID = $transaction['unique_id'];
            $Transaction->transaction_ID = $transaction['unique_id'] . '-' . $transaction['event_id'];
            array_key_exists_safe( $transaction,
                'custom_id' ) ? $Transaction->custom_ID = $transaction['custom_id'] : $Transaction->custom_ID = '';
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->commission = $transaction['commission'];
            $Transaction->currency = $transaction['currency'];
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
