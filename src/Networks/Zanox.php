<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Padosoft\AffiliateNetwork\DealsResultset;
use Padosoft\AffiliateNetwork\Product;
use Padosoft\AffiliateNetwork\ProductsResultset;
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
    public function getDeals($merchantID=NULL,int $page = 0, int $items_per_page = 0 ): DealsResultset
    {
        $result = DealsResultset::createInstance();
        if (!$this->checkLogin()) {
            return $result;
        }
        /*
        if (!isIntegerPositive($items_per_page) || $items_per_page <= 0 || $items_per_page > 999) {
            $items_per_page = 999;
        }
        */
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

        $Response = $this->_apiClient->searchIncentives($merchantID,$adSpaceId,null,null, $page, $items_per_page);
        
        if (!is_object($Response)){
            $Response=json_decode($Response);
        }
        $result->page=$Response->page;
        $result->items=$Response->items;
        $result->total=$Response->total;
        // ($Response->total>0)?$result->num_pages=(int)ceil($Response->total/$items_per_page):$result->num_pages=0;
        // Check if items exists - 2018-02-27 <PN>
        if (!property_exists($Response, 'incentiveItems')) {
            return $result;
        }
        if (!property_exists($Response->incentiveItems, 'incentiveItem')) {
            return $result;
        }
        $arrAdmediumItems = $Response->incentiveItems->incentiveItem;

        foreach ($arrAdmediumItems as $admediumItems) {
            $Deal = Deal::createInstance();
            $Deal->deal_ID = (int)$admediumItems->id;
            $Deal->start_date = $Deal->convertDate($admediumItems->startDate);
            isset($admediumItems->endDate) ? $Deal->end_date = $Deal->convertDate($admediumItems->endDate): $Deal->end_date = '2099-12-31';
            $Deal->name = $admediumItems->name;
            isset($admediumItems->couponCode) ? $Deal->code = $admediumItems->couponCode : $Deal->code = '';
            $Deal->description = $admediumItems->info4customer;
            $Deal->information = $admediumItems->info4publisher.' '.$admediumItems->restrictions;
            $Deal->is_percentage = 0;
            $Deal->discount_amount = 0;
            $Deal->currency_initial = '';
            if (isset($admediumItems->percentage) && isIntegerPositive($admediumItems->percentage)){
                $Deal->is_percentage = 1;
                $Deal->discount_amount = $admediumItems->percentage;
            }elseif (isset($admediumItems->total)){
                $Deal->discount_amount = $admediumItems->total;
                $Deal->currency_initial = $admediumItems->currency;
            }
            switch ($admediumItems->incentiveType) {
                case 'coupons':
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;
                    break;
                case 'bargains':
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_DISCOUNT;
                    break;
                case 'noShippingCosts':
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_FREE_SHIPPING;
                    break;
                case 'freeProducts':
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_FREE_ARTICLE;
                    break;
                case 'lotteries':
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_LOTTERY;
                    break;
                default:
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_DISCOUNT;
                    break;
            }
            $Deal->merchant_ID = (int)$admediumItems->program->id;
            
            $Deal->merchant_name = $admediumItems->program->_;
            if (isset($admediumItems->admedia->admediumItem[0]->trackingLinks->trackingLink)) {
                $Deal->ppv = $admediumItems->admedia->admediumItem[0]->trackingLinks->trackingLink[0]->ppv;
                $Deal->ppc = $admediumItems->admedia->admediumItem[0]->trackingLinks->trackingLink[0]->ppc;
                $Deal->default_track_uri = $Deal->ppc;
            }
            else {
                $Deal->ppv = "*** NO LINK ***";
                $Deal->ppc = "*** NO LINK ***";

            }
            $arrResult[] = $Deal;
        }
        $result->deals[] = $arrResult;
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

    /**
     * @param  array $params  this array can contains these keys
     *                        string      query          search string
     *                        string      searchType     search type (optional) (contextual or phrase)
     *                        string      region         limit search to region (optional)
     *                        int         categoryId     limit search to categorys (optional)
     *                        array       programId      limit search to program list of programs (optional)
     *                        boolean     hasImages      products with images (optional)
     *                        float       minPrice       minimum price (optional)
     *                        float       maxPrice       maximum price (optional)
     *                        int         adspaceId      adspace id (optional)
     *                        int         page           page of result set (optional)
     *                        int         items          items per page (optional)
     *
     * @return ProductsResultset
     */
    public function getProducts(array $params = []): ProductsResultset
    {
        $_params = array_merge([
            'query' => null,
            'searchType' => null,
            'query' => null,
            'searchType' => null,
            'region' => null,
            'categoryId' => null,
            'programId' => null,
            'hasImages' => null,
            'minPrice' => null,
            'maxPrice' => null,
            'adspaceId' => null,
            'page' => 0,
            'items' => 10
        ], $params);
        $products =  $this->_network->getProducts($_params);
        $set = ProductsResultset::createInstance();
        if (!property_exists($products, 'productItems') || !property_exists($products->productItems, 'productItem'))
        {
            return ProductsResultset::createInstance();
        }

        $set->page = $products->page;
        $set->items = $products->items;
        $set->total = $products->total;

        foreach ($products->productItems->productItem as $productItem) {
            $Product = Product::createInstance();
            if (property_exists($productItem, 'name')) {
                $Product->name = $productItem->name;//'Danava',
            }
            if (property_exists($productItem, 'modified')) {
                $Product->modified = $productItem->modified; //'2016-11-24T11:52:03Z',
            }
            if (property_exists($productItem, 'program')) {
                $Product->merchant_ID = $productItem->program->id; //'Twelve Thirteen DE'
                $Product->merchant_name = $productItem->program->_; //17434,
            }
            if (property_exists($productItem, 'price'))
                $Product->price = $productItem->price; //129.0
            if (property_exists($productItem, 'currency'))
                $Product->currency = $productItem->currency; //'EUR'
            if (property_exists($productItem, 'trackingLinks') && property_exists($productItem->trackingLinks, 'trackingLink')) {
                $Product->ppv = $productItem->trackingLinks->trackingLink[0]->ppv;
                $Product->ppc = $productItem->trackingLinks->trackingLink[0]->ppc;
                $Product->adspaceId = $productItem->trackingLinks->trackingLink[0]->adspaceId;
            }
            if (property_exists($productItem, 'description'))
                $Product->description = $productItem->description; //'Rosegold trifft auf puristisches Schwarz ? aufwendige und traditionelle Makramee Technik trifft auf Eleganz. Das neue Danava Buddha Armband besteht aus schwarzem Onyx, dieser Edelstein wird sehr gerne als Schmuckstein verwendet und viel lieber getragen. Der feingearbeitete rosegoldene Buddha verleiht diesem Armband einen fernöstlichen Stil. Es lässt sich wunderbar zu allen Anlässen Tragen und zu vielen Outfits kombinieren, da es Eleganz ausstrahlt. Das Symbol des Buddhas ist besonders in dieser Saison sehr gefragt.',
            if (property_exists($productItem, 'manufacturer'))
                $Product->manufacturer = $productItem->manufacturer; //'Twelve Thirteen Jewelry'
            if (property_exists($productItem, 'ean'))
                $Product->ean = $productItem->ean; //'0796716271505'
            if (property_exists($productItem, 'deliveryTime'))
                $Product->deliveryTime = $productItem->deliveryTime; //'1-3 Tage'
            if (property_exists($productItem, 'priceOld'))
                $Product->priceOld = $productItem->priceOld; //0.0
            if (property_exists($productItem, 'shippingCosts'))
                $Product->shippingCosts = $productItem->shippingCosts; //'0.0'
            if (property_exists($productItem, 'shipping'))
                $Product->shipping = $productItem->shipping; // '0.0'
            if (property_exists($productItem, 'merchantCategory'))
                $Product->merchantCategory = $productItem->merchantCategory; //'Damen / Damen Armbänder / Buddha Armbänder'
            if (property_exists($productItem, 'merchantProductId'))
                $Product->merchantProductId = $productItem->merchantProductId; //'BR018.M'
            if (property_exists($productItem, 'id'))
                $Product->id = $productItem->id; //'1ed7c3b4ab79cdbbf127cb78ec2aaff4'
            if (property_exists($productItem, 'image') && property_exists($productItem->image, 'large')) {
                $Product->image = $productItem->image->large;
            }
            $set->products[] = $Product;
        }

        return $set;
    }

    public function getTrackingParameter(){
        return $this->_tracking_parameter;
    }
}
