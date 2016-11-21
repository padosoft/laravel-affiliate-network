<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;

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

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password)
    {
        $this->_network = new \Oara\Network\Publisher\TradeDoubler;
        $this->_username = $username;
        $this->_password = $password;
        $this->_apiClient = null;
    }

    /**
     * @return bool
     */
    public function checkLogin() : bool
    {
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
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
    public function getDeals(int $merchantID = 0) : array
    {
        $arrResult = array();
        $jsonVouchers = file_get_contents("https://api.tradedoubler.com/1.0/vouchers.json;voucherTypeId=1?token=".$_ENV['TRADEDOUBLER_TOKEN']);
        $arrVouchers = json_decode($jsonVouchers, true);

        foreach($arrVouchers as $vouchers) {
            $Deal = Deal::createInstance();
            $Deal->deal_ID = $vouchers['id'];
            $Deal->merchant_ID = $vouchers['programId'];
            $Deal->merchant_name = $vouchers['programName'];
            $Deal->code = $vouchers['code'];
            $Deal->name = $vouchers['title'];
            $Deal->short_description = $vouchers['shortDescription'];
            $Deal->description = $vouchers['description'];
            $Deal->deal_type = $vouchers['voucherTypeId'];
            $Deal->default_track_uri = $vouchers['defaultTrackUri'];
            $Deal->default_track_uri = $vouchers['landingUrl'];
            $Deal->discount_amount = $vouchers['discountAmount'];
            $Deal->is_percentage = $vouchers['isPercentage'];
            $Deal->currency_initial = $vouchers['currencyId'];
            $Deal->logo_path = $vouchers['logoPath'];
            if($merchantID > 0) {
                if($vouchers['programId'] == $merchantID) {
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
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchant = array()) : array
    {
        $arrResult = array();
        $transcationList = $this->_network->getTransactionList($arrMerchant, $dateFrom, $dateTo);
        foreach($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->merchant_ID = $transaction['merchantId'];
            $date = new \DateTime($transaction['date']);
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            $Transaction->unique_ID = $transaction['unique_id'];
            $Transaction->custom_ID = $transaction['custom_id'];
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->commission = $transaction['commission'];
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
}
