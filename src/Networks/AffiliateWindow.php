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
            $Merchant->merchant_ID = $merchant->id;
            $Merchant->name = $merchant->name;
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
        $result = DealsResultset::createInstance();

        if (!isset($_ENV['AWIN_API_VOUCHER_KEY'])) {
            throw new \Exception("Awin api key not defined");
        }
        $apiKey = $_ENV['AWIN_API_VOUCHER_KEY'];

        $arrResult = array();
        $arrVouchers = $this->_network->getVouchers($apiKey);

        foreach($arrVouchers as $obj_voucher) {

            $voucher = str_getcsv($obj_voucher, ',', '"');

            if (count($voucher) < 17) {
                continue;
            }
            $promotionId = $voucher[0];
            if (!is_numeric($promotionId)) {
                continue;
            }
            $advertiser = $voucher[1];
            $advertiserId = $voucher[2];
            $type = $voucher[3];
            $code = $voucher[4];
            $description = $voucher[5];
            $starts = $voucher[6];
            $ends = $voucher[7];
            $categories = $voucher[8];
            $regions = $voucher[9];
            $terms = $voucher[10];
            $deeplink_tracking = $voucher[11];
            $deeplink = $voucher[12];
            $commission_group = $voucher[13];
            $commission = $voucher[14];
            $exclusive = $voucher[15];
            $date_added = $voucher[16];

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
            // Added timezone parameter
            $transcationList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo, 'UTC');

            if (is_array($transcationList)) {
                //echo "stepC ";
                foreach ($transcationList as $transaction) {
                    try {
/*
                        var_dump(json_encode($transaction));
                        echo "<br><br><br><br>";
*/
                        $myTransaction = Transaction::createInstance();

                        $myTransaction->merchant_ID = $transaction->advertiserId;
                        $myTransaction->date = $transaction->transactionDate;
                        //echo $transaction->transactionDate."<br>";
                        if (!empty($transaction->transactionDate)) {
                            $date = new \DateTime($transaction->transactionDate, new \DateTimeZone('UTC'));
                            $myTransaction->date = $date; // $date->format('Y-m-d H:i:s');
                            //var_dump($date);
                        }
                        $myTransaction->unique_ID = $transaction->id;
                        if (is_object($transaction->clickRefs)) {
                            if (property_exists($transaction->clickRefs,'clickRef') && $transaction->clickRefs->clickRef != null && $transaction->clickRefs->clickRef != 0)
                                $myTransaction->custom_ID = $transaction->clickRefs->clickRef;
                            else if (property_exists($transaction->clickRefs,'clickRef2') && $transaction->clickRefs->clickRef2 != null && $transaction->clickRefs->clickRef2 != 0)
                                $myTransaction->custom_ID = $transaction->clickRefs->clickRef2;
                            else if (property_exists($transaction->clickRefs,'clickRef3') && $transaction->clickRefs->clickRef3 != null && $transaction->clickRefs->clickRef3 != 0)
                                $myTransaction->custom_ID = $transaction->clickRefs->clickRef3;
                            else if (property_exists($transaction->clickRefs,'clickRef4') && $transaction->clickRefs->clickRef4 != null && $transaction->clickRefs->clickRef4 != 0)
                                $myTransaction->custom_ID = $transaction->clickRefs->clickRef4;
                            else if (property_exists($transaction->clickRefs,'clickRef5') && $transaction->clickRefs->clickRef5 != null && $transaction->clickRefs->clickRef5 != 0)
                                $myTransaction->custom_ID = $transaction->clickRefs->clickRef5;
                            else if (property_exists($transaction->clickRefs,'clickRef6') && $transaction->clickRefs->clickRef6 != null && $transaction->clickRefs->clickRef6 != 0)
                                $myTransaction->custom_ID = $transaction->clickRefs->clickRef6;
                        }

                        $myTransaction->status = \Oara\Utilities::STATUS_PENDING;
                        if ($transaction->commissionStatus == 'approved') {
                            $myTransaction->status = \Oara\Utilities::STATUS_CONFIRMED;
                        } else if ($transaction->commissionStatus == 'pending') {
                            $myTransaction->status = \Oara\Utilities::STATUS_PENDING;
                        } else if ($transaction->commissionStatus == 'declined') {
                            $myTransaction->status = \Oara\Utilities::STATUS_DECLINED;
                        }
                        //echo $transaction->saleAmount->amount."<br>";
                        $myTransaction->amount = \Oara\Utilities::parseDouble($transaction->saleAmount->amount);
                        $myTransaction->commission = \Oara\Utilities::parseDouble($transaction->commissionAmount->amount);
                        $myTransaction->currency = $transaction->commissionAmount->currency;    // 2018-03-26 <PN>
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
