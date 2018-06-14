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
use \Oara\Network\Publisher\ImpactRadius as OaraImpactRadius;

/**
 * Class ImpactRadius
 * @package Padosoft\AffiliateNetwork\Networks
 */
class ImpactRadius extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_apiClient = null;
    private $_username = '';
    private $_password = '';
    private $_logged    = false;
    protected $_tracking_parameter    = 'subId1';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $password)
    {
        $this->_network = new OaraImpactRadius;

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
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_password;
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
            $Merchant->url = $merchant['url'];
            $Merchant->status = $merchant['status'];
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
        $arrResult = array();

        // TODO
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

        try {
            // Added timezone parameter
            $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo, 'UTC');

            if (is_array($transactionList)) {
                foreach ($transactionList as $transaction) {
                    try {
                        $myTransaction = Transaction::createInstance();
                        $myTransaction->merchant_ID = $transaction['merchantId'];
                        $myTransaction->unique_ID = $transaction['unique_id'];
                        $myTransaction->date = $transaction['date'];
                        $myTransaction->custom_ID = $transaction['custom_id'];
                        $myTransaction->amount = $transaction['amount'];
                        $myTransaction->commission = $transaction['commission'];
                        $myTransaction->currency = $transaction['currency'];
                        $myTransaction->status = $transaction['status'];
                        $arrResult[] = $myTransaction;
                    } catch (\Exception $e) {
                        echo "<br><br>Transaction Error Impact Radius, id: ".$myTransaction->unique_ID." msg: ".$e->getMessage()."<br><br>";
                        var_dump($e->getTraceAsString());
                    }
                }
            }
        } catch (\Exception $e) {
            echo "<br><br>Generic Error Impact Radius: ".$e->getMessage()."<br><br>";
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
        // TODO
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

    /**
     * @return string
     */
    public function getTrackingParameter(){
        return $this->_tracking_parameter;
    }
}
