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

// require "../vendor/fubralimited/php-oara/Oara/Network/Publisher/CommissionJunction/Zapi/ApiClient.php";

/**
 * Class CommissionJunction
 * @package Padosoft\AffiliateNetwork\Networks
 */
class CommissionJunction extends AbstractNetwork implements NetworkInterface
{
    /**
     * @var object
     */
    private $_network = null;
    // private $_apiClient = null;
    private $_username = '';
    private $_password = '';
    private $_passwordApi = '';
    private $_publisher_id = '';
    protected $_tracking_parameter = 'sid';

    /**
     * @method __construct
     */
    public function __construct(string $username, string $passwordApi, $idSite)
    {
        $this->_network = new \Oara\Network\Publisher\CommissionJunctionGraphQL();
        $this->_username = $username;
        $this->_password = $passwordApi;
        $this->_passwordApi = $passwordApi;
        $this->_publisher_id = $idSite;

        if (trim($idSite) != '') {
            $this->addAllowedSite($idSite);
        }

        $this->login($this->_username, $this->_password, $this->_publisher_id);
    }

    /**
     * @return bool
     */
    public function login(string $username, string $password, $idSite): bool
    {
        $this->_logged = false;
        if (isNullOrEmpty($username) && isNullOrEmpty($password)) {
            return false;
        }
        $this->_username = $username;
        $this->_password = $password;
        $this->_passwordApi = $password;
        $this->_publisher_id = $idSite;
        $credentials = array();
        $credentials["user"] = $this->_username;
        $credentials["password"] = $this->_username;
        $credentials["apipassword"] = $this->_passwordApi;
        $credentials["id_site"] = $idSite;

        if (trim($idSite) != '') {
            $this->addAllowedSite($idSite);
        }

        $this->_network->login($credentials);
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
        $arrResult = array();
        $merchantList = $this->_network->getMerchantList();
        foreach ($merchantList as $merchant) {
            if ($merchant['status'] == 'Setup') {
                // Ignore setup programs not yet active
                continue;
            }
            $Merchant = Merchant::createInstance();
            $Merchant->merchant_ID = $merchant['cid'];
            $Merchant->name = $merchant['name'];
            // Added more info - 2018-04-23 <PN>
            $Merchant->url = $merchant['url'];
            if ($merchant['status'] == 'Active') {
                $Merchant->status = $merchant['relationship_status'];
            } else {
                $Merchant->status = $merchant['status'];
            }
            $arrResult[] = $Merchant;
        }

        return $arrResult;
    }

    /**
     * @param null | int $merchantID
     * @param int $page
     * @param int $records_per_page
     * @return DealsResultset array of Deal
     * https://developers.cj.com/docs/rest-apis/link-search
     */
    public function getDeals($merchantID = NULL, int $page = 1, int $records_per_page = 100): DealsResultset
    {
        if (empty($page)) {
            $page = 1;
        }
        if (empty($records_per_page)) {
            $records_per_page = 100;
        }
        if (empty($merchantID)) {
            $merchantID = 'joined';
        }
        $arrResult = new DealsResultset();
        $arrResult->items = $records_per_page;

        while ((int)$arrResult->items >= (int)$records_per_page) {

            try {
                //<JC> 2017-10-23  (valid keys are: advertiser-ids, category, event-name, keywords, language, link-type, page-number, promotion-end-date, promotion-start-date, promotion-type, records-per-page, website-id)
                $response = $this->_apiCall(
                    'https://link-search.api.cj.com/v2/link-search?website-id=' . $_ENV['CJ_API_WEBSITE_ID'] .
                    '&advertiser-ids=' . $merchantID .
                    '&records-per-page=' . $records_per_page .
                    '&page-number=' . $page .
                    '&promotion-type=coupon'
                );

                if ($response === false || \preg_match("/<error-message>/", $response)) {
                    preg_match('/<error-message>(.*)<\/error-message>/', $response, $matches);
                    $error_msg = $matches[1] ?? $response;
                    echo "[CommissionJunction][Error] " . $error_msg . PHP_EOL;
                    var_dump($error_msg);
                    throw new \Exception($error_msg);
                }

                $arrResponse = xml2array($response);

                if (!is_array($arrResponse) || count($arrResponse) <= 0) {
                    return $arrResult;
                }
                if (!isset($arrResponse['cj-api']['links'])) {
                    return $arrResult;
                }
                if (!isset($arrResponse['cj-api']['links']['link'])) {
                    return $arrResult;
                }
                $arrResult->page = $arrResponse['cj-api']['links_attr']['page-number'];
                $arrResult->items = $arrResponse['cj-api']['links_attr']['records-returned'];
                $arrResult->total = $arrResponse['cj-api']['links_attr']['total-matched'];
                ($arrResult->total > 0) ? $arrResult->num_pages = (int)ceil($arrResult->total / $records_per_page) : $arrResult->num_pages = 0;

                $a_links = $arrResponse['cj-api']['links']['link'];
                foreach ($a_links as $link) {
                    if (!isset($link['link-id'])) {
                        continue;
                    }
                    $Deal = Deal::createInstance();
                    $Deal->deal_ID = $link['link-id'];
                    $Deal->name = $link['link-name'];
                    $Deal->language = $link['language'];
                    $Deal->description = $link['description'];
                    $Deal->note = $link['description'];
                    $Deal->merchant_ID = $link['advertiser-id'];
                    $Deal->merchant_name = $link['advertiser-name'];
                    $Deal->default_track_uri = $link['clickUrl'];
                    $Deal->description = $link['description'];
                    $Deal->title = $link['link-name'];
                    if (!empty($link['promotion-start-date'])) {
                        $startDate = new \DateTime($link['promotion-start-date']);
                        $Deal->start_date = $startDate;
                    }
                    if (!empty($link['promotion-end-date'])) {
                        $endDate = new \DateTime($link['promotion-end-date']);
                        $Deal->end_date = $endDate;
                    }
                    $Deal->code = $link['coupon-code'];
                    $Deal->deal_type = \Oara\Utilities::OFFER_TYPE_VOUCHER;
                    $arrResult->deals[0][] = $Deal;
                }
                $page++;
            } catch (\Exception $e) {
                echo "[CommissionJunction][Error] " . $e->getMessage() . PHP_EOL;
                var_dump($e->getTraceAsString());
                throw new \Exception($e);
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
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()): array
    {
        $arrResult = array();
        // User passed $arrMerchantID, don't fill it with active merchants only - 2017-10-12 <PN>
        /*
        if (count( $arrMerchantID ) < 1) {
            $merchants = $this->getMerchants();
            foreach ($merchants as $merchant) {
                $arrMerchantID[$merchant->merchant_ID] = ['cid' => $merchant->merchant_ID, 'name' => $merchant->name];
            }
        }
        */
        $transactionList = $this->_network->getTransactionList($arrMerchantID, $dateFrom, $dateTo);
        //echo "<br>merchants id array<br>".print_r($arrMerchantID);
        //$counter=0;
        foreach ($transactionList as $transaction) {
            $Transaction = Transaction::createInstance();
            $Transaction->status = $transaction['status'];
            $Transaction->amount = $transaction['amount'];
            $Transaction->custom_ID = $transaction['custom_id'];
            $Transaction->unique_ID = $transaction['unique_id'];
            // Use 'original-action-id' instead of 'order-id' as reference field between original commission and adjust/correction commission - 2018-07-13 <PN>
            // $Transaction->transaction_ID = $transaction['order-id'];
            $Transaction->transaction_ID = $transaction['original-action-id'];
            $Transaction->commission = $transaction['commission'];
            if (!empty($transaction['date'])) {
                $date = new \DateTime($transaction['date']);
                $Transaction->date = $date; // $date->format('Y-m-d H:i:s');
            }
            $Transaction->merchant_ID = $transaction['merchantId'];
            $Transaction->original = $transaction['original'];
            //original	Displays either a '1' indicating an original transaction or a '0' indicating a non-original or correction transaction.
            // considero transazioni valide solo quelle di tipo original come viene fatto dal report consultabile sul sito web di c.j.
            // Don't check for 'original' to get DECLINED transactions - 2017-12-13 <PN>
            // if ($transaction['original'] == 'true') {
            $arrResult[] = $Transaction;
            // }
            /*
            echo "custom_id ".$transaction['custom_id']." unique_id ".$transaction['unique_id']." aid ".$transaction['aid']." commission-id ".$transaction['commission-id'].
            " order-id ".$transaction['order-id']." original ".$transaction['original']."<br>";
            */

        }
        //echo "<br>num transazioni: ".$counter;
        return $arrResult;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
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
     * @param array $params
     *
     * @return ProductsResultset
     */
    public function getProducts(array $params = []): ProductsResultset
    {
        // TODO: Implement getProducts() method.
        throw new \Exception("Not implemented yet");
    }

    /**
     * Api call CommissionJunction
     */
    private function _apiCall($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if (!empty($this->_publisher_id)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $this->_passwordApi));
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: " . $this->_passwordApi));
        }

        $curl_results = curl_exec($ch);
        curl_close($ch);
        return $curl_results;
    }


    public function getTrackingParameter()
    {
        return $this->_tracking_parameter;
    }

    public function addAllowedSite($idSite)
    {
        if (trim($idSite) != '') {
            $this->_network->addAllowedSite($idSite);
        }
    }

}
