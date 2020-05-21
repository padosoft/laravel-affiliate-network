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
 * Class PepperJamApi
 * @package Padosoft\AffiliateNetwork\Networks
 */
class PepperJamApi extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    protected $_tracking_parameter = 'sid';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password)
    {
        $apiKey = $_ENV["PEPPER_JAM_API_KEY"];
        $this->_network = new \Oara\Network\Publisher\PepperJamApi($apiKey);
    }

    public function checkLogin()
    {
        return true;
    }

    /**
     * @return array of Merchants
     * @throws \Exception
     */
    public function getMerchants() : array
    {
        return array_map(function ($rawMerchant) {
            $merchant = new Merchant();
            $merchant->merchant_ID = $rawMerchant['cid'];
            $merchant->name = $rawMerchant['name'];
            $merchant->status = $rawMerchant['status'];
            $merchant->url = $rawMerchant['url'];
            if (!empty($rawMerchant['application_date'])) {
                $merchant->application_date = new \DateTime($rawMerchant['application_date']);
            }
            return $merchant;
        }, $this->_network->getMerchantList());
    }

    /**
     * @param null $merchantID
     * @param int $page
     * @param int $items_per_page
     * @return DealsResultset  array of Deal
     * @throws \Exception
     */
    public function getDeals($merchantID = null, int $page = 0, int $items_per_page = 100): DealsResultset
    {
        throw new \Exception("Not implemented yet");
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
        return array_map(function ($rawTransaction) {
            $transaction = new Transaction();
            $transaction->status = $rawTransaction['status'];
            $transaction->unique_ID = $rawTransaction['unique_id'];
            $transaction->commission = $rawTransaction['commission'];
            $transaction->amount = $rawTransaction['amount'];
            $transaction->date = new \DateTime($rawTransaction['date']);
            $transaction->merchant_ID = $rawTransaction['program_id'];
            $transaction->campaign_name =  $rawTransaction['program_name'];
            $transaction->custom_id = $rawTransaction['custom_id'];

            $transaction->approved = false;
            if ($transaction->status == \Oara\Utilities::STATUS_CONFIRMED) {
                $transaction->approved = true;
            }
            return $transaction;
        }, $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo));
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
     * @param array $params
     * @return ProductsResultset
     * @throws \Exception
     */
    public function getProducts(array $params = []): ProductsResultset
    {
        throw new \Exception("Not implemented yet");
    }

    public function getTrackingParameter()
    {
        return $this->_tracking_parameter;
    }
}
