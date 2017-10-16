<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\ProductsResultset;

/**
 * Class Skimlinks
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Skimlinks extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_apiClient = null;
    private $_password = '';
    private $_idSite = '';
    protected $_country = '';
    protected $_tracking_parameter    = 'xcust';



    /**
     * @method __construct
     */
    public function __construct(string $username, string $password, string $idSite = '', string $country = '')
    {
        $this->_network = new \Oara\Network\Publisher\Skimlinks;
        $this->_username = $username;
        $this->_password = $password;
        $this->login( $this->_username, $this->_password, $idSite, $this->_country);
        $this->_apiClient = null;

        if (trim($idSite)!=''){
            $this->addAllowedSite($idSite);
        }
    }

    public function login(string $username, string $password, string $idSite = '', string $country = ''): bool{
        $this->_logged = false;
        if (isNullOrEmpty( $password )) {
            return false;
        }
        $this->_username = $username;

        //<JC> Split public and private api key
        $apis_key = explode("|", $password);
        $public_key = str_replace("pub=", "", $apis_key[0]);
        $private_key = str_replace("priv=", "", $apis_key[1]);

        $this->_password = $private_key;
        $this->_idSite = $idSite;

        if (trim($idSite)!=''){
            $this->addAllowedSite($idSite);
        }

        if (strpos($country, '-') !== false) {
            $a_country = explode("-", $country);
            $country = $a_country[1];
        }
        $this->_country = strtoupper($country);

        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["apikey"] = $public_key;
        $credentials["private_apikey"] = $private_key;
        $credentials["id_site"] = $idSite;

        $credentials['country'] = $country;
        $this->_network->login($credentials);
        $this->_logged = true;

        return $this->_logged;
    }

    public function addAllowedSite($idSite){
        if (trim($idSite)!=''){
            $this->_network->addAllowedSite($idSite);
        }
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
            $Merchant->merchant_ID = $merchant['id'];
            $Merchant->name = $merchant['name'];
            $arrResult[] = $Merchant;
        }

        return $arrResult;
    }

    /**
     * @param int $merchantID
     * @return array of Deal
     */
    public function getDeals($merchantID=NULL,int $page=0,int $items_per_page=10 ): DealsResultset
    {
        $arrResult = array();
        return $arrResult;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param array $arrMerchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array
    {
        $arrResult = array();
        try {
             $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);

            foreach($transactionList as $transaction) {
                
                $myTransaction = Transaction::createInstance();
                try {
                    $myTransaction->merchant_ID = $transaction['merchantId'];
                    $myTransaction->title ='';
                    $myTransaction->currency = $transaction['currency'];
                    if (!empty($transaction['date'])) {
                        $date = new \DateTime($transaction['date']);
                        $myTransaction->date = $date; // $date->format('Y-m-d H:i:s');
                    }
                    if (!empty($transaction['last_updated'])) {
                        $date = new \DateTime($transaction['last_updated']);
                        $myTransaction->update_date = $date; // $date->format('Y-m-d H:i:s');
                    }
                    if (!empty($transaction['click_date'])){
                        $date = new \DateTime($transaction['click_date']);
                        $myTransaction->click_date = $date; // $date->format('Y-m-d H:i:s');
                    }
                    $myTransaction->unique_ID = $transaction['unique_id'];
                    $myTransaction->custom_ID = array_key_exists('custom_id', $transaction) ? $transaction['custom_id'] : '';
                    $myTransaction->status = $transaction['status'];
                    $myTransaction->amount = $transaction['amount'];
                    $myTransaction->commission = $transaction['commission'];
                    $myTransaction->approved = false;
                    if ($transaction['status'] == \Oara\Utilities::STATUS_CONFIRMED){
                        $myTransaction->approved = true;
                    }
                    $account_id = $transaction['publisher_id'];
                    $id_site = $transaction['publisher_domain_id'];

                    $arrResult[] = $myTransaction;
                } catch (\Exception $e) {
                    echo "<br><br>errore transazione Skimlinks, id: ".$myTransaction->unique_ID." msg: ".$e->getMessage()."<br><br>";
                    var_dump($e->getTraceAsString());
                }
            }
        } catch (\Exception $e) {
            echo "<br><br>errore generico transazione Skimlinks: ".$e->getMessage()."<br><br>";
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
