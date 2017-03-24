<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\Transaction;
use Padosoft\AffiliateNetwork\Merchant;
use Padosoft\AffiliateNetwork\Stat;
use Padosoft\AffiliateNetwork\Deal;
use Padosoft\AffiliateNetwork\AbstractNetwork;
use Padosoft\AffiliateNetwork\NetworkInterface;
use Padosoft\AffiliateNetwork\ZanoxEx;

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/Zanox/Zapi/ApiClient.php";

/**
 * Class Zanox
 * @package Padosoft\AffiliateNetwork\Networks
 */
class Zanox extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network   = null;
    protected $_apiClient = null;
    private $_username  = '';
    private $_password  = '';
    private $_logged    = false;
    protected $_tracking_parameter    = 'zpar0';


    /**
     * @method __construct
     */
    public function __construct(string $username, string $password,string $idSite='')
    {
        $this->_network = new ZanoxEx;
        $this->_username = $username;
        $this->_password = $password;
        $this->login( $this->_username, $this->_password );

    }

    public function login(string $username, string $password,string $idSite=''): bool
    {
        $this->_logged = false;
        if (isNullOrEmpty( $username ) || isNullOrEmpty( $password )) {

            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $credentials = array();
        $credentials["connectid"] = $this->_username;
        $credentials["secretkey"] = $this->_password;
        $this->_network->login( $credentials );
        $this->_apiClient = $this->_network->getApiClient();
        if ($this->_network->checkConnection()) {
            $this->_logged = true;

        }

        return $this->_logged;
    }

    /**
     * @return bool
     */
    public function checkLogin(): bool
    {
        return $this->_logged;
    }

    /**
     * @return array of Merchants
     */
    public function getMerchants(): array
    {
        if (!$this->checkLogin()) {
            return array();
        }
        $arrResult = array();
        $merchantList = $this->_network->getMerchantList();
        foreach ($merchantList as $merchant) {
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
    public function getDeals($merchantID=NULL,int $page=0,int $items_per_page=10 ): DealsResultset
    {
        if (!isIntegerPositive($items_per_page)){
            $items_per_page=10;
        }
        $result=DealsResultset::createInstance();
        if (!$this->checkLogin()) {
            return $result;
        }
        /*$this->_apiClient->setConnectId( $this->_username );
        $this->_apiClient->setSecretKey( $this->_password );*/
        $adSpaces=$this->_apiClient->getAdspaces(0,100);
        if (!is_object($adSpaces)){
            $adSpaces=json_encode($adSpaces);
        }
        if ($adSpaces->items<1){
            return $result;
        }

        $adSpaceId=$adSpaces->adspaceItems->adspaceItem[0]->id;
        if (!isIntegerPositive($merchantID)){
            $merchantID=NULL;
        }

        $Response = $this->_apiClient->searchIncentives($merchantID,$adSpaceId,'coupons',NULL,$page,$items_per_page);
        
        if (!is_object($Response)){
            $Response=json_decode($Response);
        }
        $result->page=$Response->page;
        $result->items=$Response->items;
        $result->total=$Response->total;
        ($Response->total>0)?$result->num_pages=(int)ceil($Response->total/$items_per_page):$result->num_pages=0;
        $arrAdmediumItems = $Response->incentiveItems->incentiveItem;

        foreach ($arrAdmediumItems as $admediumItems) {
            $Deal = Deal::createInstance();
            $Deal->id = (int)$admediumItems->id;
            $Deal->created_at =$admediumItems->createDate;
            $Deal->startDate = $admediumItems->startDate;
            isset($admediumItems->endDate)?$Deal->endDate = $admediumItems->endDate:$Deal->endDate = '';
            $Deal->name = $admediumItems->name;
            $Deal->code = $admediumItems->couponCode;
            $Deal->description = $admediumItems->info4customer;
            $Deal->note = $admediumItems->info4publisher.' '.$admediumItems->restrictions;
            $Deal->is_percent = 0;
            $Deal->value=0;
            $Deal->currency='';
            //dd($admediumItems->percentage);
            if (isset($admediumItems->percentage) && isIntegerPositive($admediumItems->percentage)){
                $Deal->is_percent = 1;
                $Deal->value=$admediumItems->percentage;
            }elseif (isset($admediumItems->total)){

                $Deal->value=$admediumItems->total;
                $Deal->currency=$admediumItems->currency;
            }
            //$Deal->deal_type = $admediumItems['admediumType'];
            $Deal->merchant_ID = (int)$admediumItems->program->id;
            
            $Deal->merchant_name = $admediumItems->program->_;
            $Deal->ppv = $admediumItems->admedia->admediumItem[0]->trackingLinks->trackingLink[0]->ppv;
            $Deal->ppc = $admediumItems->admedia->admediumItem[0]->trackingLinks->trackingLink[0]->ppc;
            $result->deals[]=$Deal;
            /*if ($merchantID > 0) {
                if ($merchantID == $admediumItems['program']['@id']) {
                    $arrResult[] = $Deal;
                }
            } else {
                $arrResult[] = $Deal;
            }*/
        }
        //dd($result);
        return $result;
    }


    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     *
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()): array
    {
        if (!$this->checkLogin()) {
            return array();
        }
        $dateFrom2=new \DateTime($dateFrom->format('Y-m-d'));
        if ($dateTo->format('Y-m-d')==$dateFrom2->format('Y-m-d')){
            $dateFrom2->sub(new \DateInterval('P1D'));
        }

        $arrResult = array();
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        $transcationList = $this->_network->getTransactionList( $arrMerchantID, $dateFrom2, $dateTo );
        foreach ($transcationList as $transaction) {
            $Transaction = Transaction::createInstance();
            array_key_exists_safe( $transaction,
                'currency' ) ? $Transaction->currency = $transaction['currency'] : $Transaction->currency = '';
            array_key_exists_safe( $transaction,
                'status' ) ? $Transaction->status = $transaction['status'] : $Transaction->status = '';
            array_key_exists_safe( $transaction,
                'amount' ) ? $Transaction->amount = $transaction['amount'] : $Transaction->amount = '';
            array_key_exists_safe( $transaction,
                'custom_id' ) ? $Transaction->custom_ID = $transaction['custom_id'] : $Transaction->custom_ID = '';
            array_key_exists_safe( $transaction,
                'title' ) ? $Transaction->title = $transaction['title'] : $Transaction->title = '';
            array_key_exists_safe( $transaction,
                'unique_id' ) ? $Transaction->unique_ID = $transaction['unique_id'] : $Transaction->unique_ID = '';
            array_key_exists_safe( $transaction,
                'commission' ) ? $Transaction->commission = $transaction['commission'] : $Transaction->commission = '';
            $date = new \DateTime( $transaction['date'] );
            $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            array_key_exists_safe( $transaction,
                'merchantId' ) ? $Transaction->merchant_ID = $transaction['merchantId'] : $Transaction->merchant_ID = '';
            array_key_exists_safe( $transaction,
                'approved' ) ? $Transaction->approved = $transaction['approved'] : $Transaction->approved = '';
            $arrResult[] = $Transaction;
        }

        return $arrResult;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     *
     * @return array of Stat
     */
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0): array
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

    public function getTrackingParameter(){
        return $this->_tracking_parameter;
    }
}
