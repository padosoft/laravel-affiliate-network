<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\AffiliateWindowEx;
use Padosoft\AffiliateNetwork\ProductsResultset;

/**
 * Class TradeDoubler
 * @package Padosoft\AffiliateNetwork\Networks
 */
class AffiliateWindow extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_apiClient = null;
    private $_username = '';
    private $_password = '';
    private $_logged    = false;
    protected $_tracking_parameter    = 'clickref';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password)
    {
        $this->_network = new AffiliateWindowEx;
        $this->_username = $username;
        $this->_password = $password;
        $this->_apiClient = null;
        $this->login( $this->_username, $this->_password );
    }

    public function login(string $username, string $password): bool
    {
        $this->_logged = true;
        if (isNullOrEmpty( $username ) || isNullOrEmpty( $password )) {
            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $credentials = array();
        $credentials["accountid"] = $this->_username;
        $credentials["apipassword"] = $this->_password;
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
    {/*
        if (!$this->checkLogin()) {
            return array();
        }*/
        //echo "go";
        $arrResult = array();
        /*
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }*/

        try {
            //echo "stepA ";
            $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
            //echo "stepB ";
            if (is_array($transcationList)) {
                //echo "stepC ";
                foreach ($transcationList as $transaction) {
                    try {
                        $myTransaction = Transaction::createInstance();

                        $myTransaction->merchant_ID = $transaction->advertiserId;
                        $myTransaction->date = $transaction->transactionDate;
                        //echo $transaction->transactionDate."<br>";
                        if (!empty($transaction->transactionDate)) {
                            $date = new \DateTime($transaction->transactionDate);
                            $myTransaction->date = $date; // $date->format('Y-m-d H:i:s');
                            //var_dump($date);
                        }
                        $myTransaction->unique_ID = $transaction->id;
                        if (is_object($transaction->clickRefs)) {
                            if (property_exists($transaction->clickRefs,'clickRef') && $transaction->clickRefs->clickRef != null)
                                $myTransaction->custom_ID = $transaction->clickRefs->clickRef;
                            else if (property_exists($transaction->clickRefs,'clickRef2') && $transaction->clickRefs->clickRef2 != null)
                                $myTransaction->custom_ID = $transaction->clickRefs->clickRef2;
                        }

                        if ($transaction->commissionStatus == 'approved') {
                            $myTransaction->status = \Oara\Utilities::STATUS_CONFIRMED;
                        } else if ($transaction->commissionStatus == 'pending') {
                            $myTransaction->status = \Oara\Utilities::STATUS_PENDING;
                        } else if ($transaction->commissionStatus == 'pending') {
                            $myTransaction->status = \Oara\Utilities::STATUS_DECLINED;
                        }
                        //echo $transaction->saleAmount->amount."<br>";
                        $myTransaction->amount = \Oara\Utilities::parseDouble($transaction->saleAmount->amount);
                        $myTransaction->commission = \Oara\Utilities::parseDouble($transaction->commissionAmount->amount);
                        $arrResult[] = $myTransaction;
                    } catch (\Exception $e) {
                        //echo "stepE ";
                        echo "<br><br>errore transazione AffiliateWindow, id: ".$myTransaction->unique_ID." msg: ".$e->getMessage()."<br><br>";
                        var_dump($e->getTraceAsString());
                        //throw new \Exception($e);
                    }
                }
            }
            //echo "stepD ";
        } catch (\Exception $e) {
            //echo "stepE ";
            echo "<br><br>errore generico transazione AffiliateWindow: ".$e->getMessage()."<br><br>";
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
