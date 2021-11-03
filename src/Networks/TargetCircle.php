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

if (!defined('COOKIES_BASE_DIR')){
    define('COOKIES_BASE_DIR',public_path('upload/report'));
}
/**
 * Class TargetCircle
 * @package Padosoft\AffiliateNetwork\Networks
 */
class TargetCircle extends AbstractNetwork implements NetworkInterface
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
    protected $_tracking_parameter    = 'click_id';

    /**
     * TargetCircle constructor.
     * @param string $username
     * @param string $password
     * @param string $idSite
     */
    public function __construct(string $username, string $password, string $idSite = '')
    {
        $this->_network = new \Oara\Network\Publisher\TargetCircle;
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
        $credentials["user"] = null; // not used
        $credentials["password"] = $this->_password;
        $credentials["idSite"] = null; // not used
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
        throw new \Exception("Not implemented yet");
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
            $Transaction->date = $transaction['date']; // already a datetime object
            $Transaction->click_date = $transaction['click_date'];
            if (isset($transaction['update_date']) && !empty($transaction['update_date'])){
                $Transaction->update_date = $transaction['update_date'];
            }
            $Transaction->transaction_ID = $transaction['unique_id'];
            $Transaction->unique_ID = $transaction['unique_id'];
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
