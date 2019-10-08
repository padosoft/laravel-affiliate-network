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
 * Class Daisycon
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Daisycon extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_username = '';
    private $_password = '';
    private $_apiClient = null;
    protected $_tracking_parameter = 'ws';
    private $_idSite = '';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password, string $idSite = '')
    {
        $this->_network = new \Oara\Network\Publisher\Daisycon;
        $this->_username = $username;
        $this->_password = $password;
        $idSite = $this->_idSite;
        $this->login( $this->_username, $this->_password, $idSite );
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
        $credentials["idSite"] = $idSite;
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
            $Merchant->url = $merchant['display_url'];
            $Merchant->status = $merchant['status'];
            if (!empty($merchant['start_date'])) {
                if ($merchant['start_date'] == '0000-00-00') {
                    $Merchant->launch_date = null;
                }
                else {
                    $date = new \DateTime($merchant['start_date']);
                    //TODO check date format
                    //$Merchant->launch_date = $date;
                }
            }
            if (!empty($merchant['end_date'])) {
                if ($merchant['end_date'] == '0000-00-00') {
                    $Merchant->termination_date = null;
                }
                else {
                    $date = new \DateTime($merchant['end_date']);
                    $Merchant->termination_date = $date;
                }
            }
            $arrResult[] = $Merchant;
        }
        return $arrResult;
    }

    /**
     * @param int $merchantID
     * @return array of Deal
     */
    public function getDeals($merchantID = null,int $page = 0,int $items_per_page = 100 ): DealsResultset
    {
        $arrResult = array();

        $result = DealsResultset::createInstance();

        $arrVouchers = $this->_network->getVouchers();

        foreach($arrVouchers as $voucher) {
            if (!empty($voucher['promotioncode']) && !empty($voucher['program_id'])) {
                $Deal = Deal::createInstance();
                $Deal->deal_ID = md5($voucher['program_id'] . $voucher['id']);    // generate a unique deal ID
                $Deal->merchant_ID = $voucher['program_id'];
                $Deal->code = $voucher['promotioncode'];
                $Deal->name = $voucher['name'];
                $Deal->description =  $voucher['description'];
                $Deal->start_date = $Deal->convertDate($voucher['start_date']);
                $Deal->start_date->setTime(0, 0, 0);
                $Deal->end_date = $Deal->convertDate($voucher['end_date']);
                $Deal->end_date->setTime(23, 59, 59);
                $Deal->default_track_uri = $voucher['click_url'];
                if (substr($Deal->default_track_uri,0,2) == '//') {
                    // Special case... add https:
                    $Deal->default_track_uri = 'https:' . $Deal->default_track_uri;
                }
                $Deal->is_exclusive = false;
                $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;
                if ($voucher['measure'] == 'percentage') {
                    $Deal->is_percentage = true;
                }
                else {
                    $Deal->is_percentage = false;
                }
                $Deal->discount_amount = $voucher['amount'];
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
        $arrResult = array();
        $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
        foreach($transactionList as $transaction) {
            $Transaction = Transaction::createInstance();
            if (isset($transaction['currency']) && !empty($transaction['currency'])) {
                $Transaction->currency = $transaction['currency'];
            } else {
                $Transaction->currency = "EUR";
            }
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            array_key_exists_safe( $transaction,'custom_id' ) ? $Transaction->custom_ID = $transaction['custom_id'] : $Transaction->custom_ID = '';
            $Transaction->title = '';
            $Transaction->unique_ID = $transaction['unique_id'];
            $Transaction->commission = $transaction['commission'];
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            // Future use - Only few providers returns these dates values - <PN> - 2017-06-29
            if (isset($transaction['click_date']) && !empty($transaction['click_date'])) {
                $Transaction->click_date = new \DateTime($transaction['click_date']);
            }
            if (isset($transaction['update_date']) && !empty($transaction['update_date'])) {
                $Transaction->update_date = new \DateTime($transaction['update_date']);
            }
            $Transaction->merchant_ID = $transaction['merchantId'];
            $Transaction->campaign_name =  $transaction['merchantName'];
            $Transaction->IP =  $transaction['IP'];
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
        throw new \Exception("Not implemented yet");
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
