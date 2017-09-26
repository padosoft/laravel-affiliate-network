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

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/Effiliation/Zapi/ApiClient.php";

/**
 * Class Effiliation
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Effiliation extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    private $_apiClient = null;
    private $_password = '';
    protected $_tracking_parameter    = 'effi_id';
    /**
     * @method __construct
     */
    public function __construct(string $username, string $password,string $idSite='')
    {
        $this->_network = new \Padosoft\AffiliateNetwork\EffiliationEx;
        $this->_username = $username;
        $this->_password = $password;
        $this->login( $this->_username, $this->_password );
        $this->_apiClient = null;
    }
    public function login(string $username, string $password,string $idSite=''): bool{
        $this->_logged = false;
        if (isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $credentials = array();
        $credentials["apiPassword"] = $this->_password;
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
        $url = 'http://api.effiliation.com/apiv2/programs.xml?key=' . $this->_password . "&filter=all";
        echo "effiliation getMerchant url ",PHP_EOL;
        //var_dump($url);
        $content = @\file_get_contents($url);
        // echo "effiliation content",PHP_EOL;
        //var_dump($content);
        $xml = \simplexml_load_string($content, null, LIBXML_NOERROR | LIBXML_NOWARNING);
        // echo "effiliation XML ",PHP_EOL;
        //var_dump($xml);
        foreach ($xml->program as $merchant) {
            $Merchant = Merchant::createInstance();
            $Merchant->merchant_ID = (string)$merchant->id_programme;
            $Merchant->name = (string)$merchant->nom;
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
        $result = DealsResultset::createInstance();

        $url = 'http://apiv2.effiliation.com/apiv2/commercialtrades.json?filter=mines&key='.$this->_password;
        $json = file_get_contents($url);

        $arrResult = array();
        $arrResponse = json_decode($json, true);
        if(!is_array($arrResponse) || count($arrResponse) <= 0 || !array_key_exists('supports', $arrResponse)) {
            return $arrResult;
        }
        $arrPrograms = $arrResponse['supports'];
        foreach($arrPrograms as $voucher) {
            $Deal = Deal::createInstance();
            $Deal->setValues($voucher, [
                'id_lien' => 'deal_ID' ,
                'id_programme' => 'merchant_ID' ,
                'date_debut' => 'start_date' ,
                'date_fin' => 'end_date' ,
                'nom' => 'name' ,
                'description' => 'description' ,
                'intitule' => 'code' ,
                'url_redir' => 'default_track_uri' ,
                'exclusivite' => 'is_exclusive' ,
                'type' => 'deal_type',
            ]);
            switch ($voucher['type']) {
                case 'Code de réduction':
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;
                    break;
                case 'Bon plan':
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_DISCOUNT;
                    break;
                default:
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_DISCOUNT;
                    break;
            }
            if($merchantID > 0) {
                if($merchantID == $voucher['id_programme']) {
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
    {
        $arrResult = array();
        try {
            if (count( $arrMerchantID ) < 1) {
                $merchants = $this->getMerchants();
                foreach ($merchants as $merchant) {
                    $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
                }
            }
             $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);

            foreach($transactionList as $transaction) {
                $myTransaction = Transaction::createInstance();
                try {
                    $myTransaction->merchant_ID = $transaction['merchantId'];
                    $myTransaction->title ='';
                    $myTransaction->currency ='EUR';
                    //echo "txdate: ".$transaction['date']."<br>";
                    if (!empty($transaction['date'])) {
                        $date = new \DateTime($transaction['date']);
                        $myTransaction->date = $date; // $date->format('Y-m-d H:i:s');
                    }
                    $myTransaction->unique_ID = $transaction['unique_id'];
                    $myTransaction->custom_ID = $transaction['custom_id'];
                    //var_dump($transaction);
                    $myTransaction->status = $transaction['status'];
                    $myTransaction->amount = $transaction['amount'];
                    $myTransaction->commission = $transaction['commission'];
                    $myTransaction->approved = false;
                    if ($transaction['status'] == \Oara\Utilities::STATUS_CONFIRMED){
                        $myTransaction->approved = true;
                    }
                    $arrResult[] = $myTransaction;
                } catch (\Exception $e) {
                    //echo "stepE ";
                    echo "<br><br>errore transazione effilitation, id: ".$myTransaction->unique_ID." msg: ".$e->getMessage()."<br><br>";
                    var_dump($e->getTraceAsString());
                    //throw new \Exception($e);
                }
            }
        } catch (\Exception $e) {
            //echo "stepE ";
            echo "<br><br>errore generico transazione effiliation: ".$e->getMessage()."<br><br>";
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
    public function getProducts(array $params = []): ProductsResultset
    {
        // TODO: Implement getProducts() method.
        throw new \Exception("Not implemented yet");
    }

    public function getTrackingParameter(){
        return $this->_tracking_parameter;
    }
}
