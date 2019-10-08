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
 * Class Admitad
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Admitad extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_username = '';
    private $_password = '';
    private $_apiClient = null;
    protected $_tracking_parameter    = '';
    private $_idSite = '';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password, string $idSite='')
    {
        $this->_network = new \Oara\Network\Publisher\Admitad;
        $this->_username = $username;
        $this->_password = $password;
        $this->_idSite = $idSite;

        $this->login( $this->_username, $this->_password, $idSite );
    }

    public function login(string $username, string $password,string $idSite=''): bool{
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
	 * @throws \Exception
	 */
    public function getMerchants() : array
    {
        $arrResult = array();
        $merchantList = $this->_network->getMerchantList();
        foreach($merchantList as $merchant) {
            $Merchant = Merchant::createInstance();
            $Merchant->merchant_ID = $merchant['cid'];
            $Merchant->name = $merchant['name'];
            $Merchant->status = $merchant['status'];
            $Merchant->url = $merchant['url'];
            //TODO check date format
            //$Merchant->launch_date = $merchant['launch_date'];
            $arrResult[] = $Merchant;
        }
        return $arrResult;
    }

    /**
	 * @param null $merchantID
	 * @param int $page
	 * @param int $items_per_page
	 * @return DealsResultset  array of Deal
	 * @throws \Exception
	 */
    public function getDeals($merchantID=NULL,int $page=0,int $items_per_page=100 ): DealsResultset
    {
        $arrResult = array();

        $result = DealsResultset::createInstance();

        $arrVouchers = $this->_network->getVouchers($this->_idSite);

        foreach($arrVouchers as $voucher) {
            if (!empty($voucher['tracking']) && !empty($voucher['advertiser_id'])) {
                $Deal = Deal::createInstance();
                $Deal->deal_ID = md5($voucher['tracking']);    // Use link to generate a unique deal ID
                $Deal->merchant_ID = $voucher['advertiser_id'];
                $Deal->merchant_name = $voucher['advertiser_name'];
                $Deal->code = $voucher['code'];
                $Deal->name = $voucher['name'];
                $Deal->description = $voucher['description'];
                $Deal->start_date = $Deal->convertDate($voucher['start_date']);
                $Deal->end_date = $Deal->convertDate($voucher['end_date']);
                $Deal->default_track_uri = $voucher['tracking'];
                $Deal->discount_amount = $voucher['discount_amount'];
                $Deal->is_percentage = $voucher['is_percentage'];
                $Deal->is_exclusive = false;
                $Deal->deal_type = $voucher['type'];
                $arrResult[] = $Deal;
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
            if ($Transaction->status == \Oara\Utilities::STATUS_CONFIRMED){
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
        // TODO
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
	 * @param array $params
	 * @return ProductsResultset
	 * @throws \Exception
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
