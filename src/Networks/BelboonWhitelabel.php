<?php
namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\ProductsResultset;

/**
 * Class BelboonWhitelabel
 * @package Padosoft\AffiliateNetwork\Networks
 */
class BelboonWhitelabel extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    protected $_tracking_parameter = 'scm';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password, string $idSite = '')
    {
        $apiKey = $_ENV["BELBOON_WHITELABEL_API_KEY"];
        $userId = $_ENV["BELBOON_WHITELABEL_USER_ID"];
        $baseUrl = $_ENV["BELBOON_WHITELABEL_BASE_URL_API"];
        $this->_network = new \Oara\Network\Publisher\BelboonWhitelabel($apiKey, $userId, $baseUrl);
    }

    /**
     * @return bool
     */
    public function checkLogin():
    bool
    {
        return true;
    }

    /**
     * @return array of Merchants
     */
    public function getMerchants():
    array
    {
        if (!$this->checkLogin())
        {
            return array();
        }
        $arrResult = array();
        $merchantList = $this
            ->_network
            ->getMerchantList();
        foreach ($merchantList as $merchant)
        {
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
    public function getDeals($merchantID = null, int $page = 0, int $items_per_page = 10):
    DealsResultset
    {
        $dealList = $this
            ->_network
            ->getVouchers();

        // only the type voucher is imported
        // possible types:
        // discount_all, discount_single, free_ship, freebie, misc, ''
        $onlyVouchersDeals = array_filter($dealList, function ($deal)
        {
            // the deal types can be multiple...
            $dealTypes = $deal["voucher_type"];
            $isDiscountAll = strpos($dealTypes, 'discount_all') != false;
            $isDiscountSingle = strpos($dealTypes, 'discount_single') != false;
            $isEmpty = $dealTypes == '';

            return $isDiscountAll || $isDiscountSingle || $isEmpty;
        });

        $result = DealsResultset::createInstance();

        $deals = array_map(function ($rawDeal)
        {
            $deal = new Deal();
            $deal->deal_ID = $rawDeal["vcid"];
            $deal->merchant_ID = $rawDeal["mid"];
            $deal->code = $rawDeal["codes"];
            $deal->description = $rawDeal["description"];
            $deal->start_date = $deal->convertDate($rawDeal["date_from"]);
            $deal->end_date = $deal->convertDate($rawDeal["date_to"]);
            $deal->landing_url = $rawDeal["landingpage"];
            $deal->discount_amount = $rawDeal["discount_amount"];
            $deal->minimum_order_value = $rawDeal["min_order_value"];
            $deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;
            return $deal;
        }
            , $onlyVouchersDeals);

        $result->deals[] = $deals;

        return $result;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()):
    array
    {
        $arrResult = array();
        try
        {
            $transactionList = $this
                ->_network
                ->getTransactionList(null, $dateFrom, $dateTo);

            foreach ($transactionList as $transaction)
            {
                $myTransaction = Transaction::createInstance();
                try
                {
                    $myTransaction->merchant_ID = $transaction['merchantId'];
                    $myTransaction->title = '';
                    $myTransaction->currency = $transaction['currency'];
                    if (!empty($transaction['date']))
                    {
                        $date = new \DateTime($transaction['date']);
                        $myTransaction->date = $date; // $date->format('Y-m-d H:i:s');

                    }
                    if (!empty($transaction['click_date']))
                    {
                        $date = new \DateTime($transaction['click_date']);
                        $myTransaction->click_date = $date; // $date->format('Y-m-d H:i:s');

                    }
                    if (!empty($transaction['lastchangedate']))
                    {
                        $date = new \DateTime($transaction['lastchangedate']);
                        $myTransaction->update_date = $date;
                    }
                    $myTransaction->unique_ID = $transaction['unique_id'];
                    $myTransaction->custom_ID = array_key_exists('custom_id', $transaction) ? $transaction['custom_id'] : '';
                    $myTransaction->status = $transaction['status'];
                    $myTransaction->amount = $transaction['amount'];
                    $myTransaction->commission = $transaction['commission'];

                    $myTransaction->approved = false;
                    if ($transaction['status'] == \Oara\Utilities::STATUS_CONFIRMED)
                    {
                        $myTransaction->approved = true;
                    }
                    $arrResult[] = $myTransaction;
                }
                catch(\Exception $e)
                {
                    echo "<br><br>errore transazione Belboon Whitelabel, id: " . $myTransaction->unique_ID . " msg: " . $e->getMessage() . "<br><br>";
                    var_dump($e->getTraceAsString());
                }
            }
        }
        catch(\Exception $e)
        {
            echo "<br><br>errore generico transazione Belboon Whitelabel: " . $e->getMessage() . "<br><br>";
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
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0):
    array
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
    public function getProducts(array $params = []):
    ProductsResultset
    {
        // TODO: Implement getProducts() method.
        throw new \Exception("Not implemented yet");
    }

    public function getTrackingParameter()
    {
        return $this->_tracking_parameter;
    }
}

