<?php
/**
 * Copyright (c) Padosoft.com 2017.
 * Created by Paolo Nardini - 2017-08-31
 */


namespace Padosoft\AffiliateNetwork;
use Oara\Network\Publisher\Ebay as EbayOara;

class EbayEx extends EbayOara
{
    protected $_serverNumber = 6;
    protected $_merchantIdList = array();     // To avoid repeated calls to \Oara\Utilities::getMerchantIdMapFromMerchantList

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);

        /*
        $valuesLogin = array(
            new \Oara\Curl\Parameter('login_username', $this->_credentials['user']),
            new \Oara\Curl\Parameter('login_password', $this->_credentials['password']),
            new \Oara\Curl\Parameter('submit_btn', 'GO'),
            new \Oara\Curl\Parameter('hubpage', 'y')
        );
        $loginUrl = 'https://ebaypartnernetwork.com/PublisherLogin?hubpage=y&lang=en-US?';

        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);
        */
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "User Log in";
        $parameter["required"] = true;
        $parameter["name"] = "User";
        $credentials["user"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Password to Log in";
        $parameter["required"] = true;
        $parameter["name"] = "Password";
        $credentials["password"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = true;
        /*
        $yesterday = new \DateTime();
        $yesterday->sub(new \DateInterval('P2D'));

        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://publisher.ebaypartnernetwork.com/PublisherReportsTx?pt=2&start_date={$yesterday->format("n/j/Y")}&end_date={$yesterday->format("n/j/Y")}&user_name={$this->_credentials['user']}&user_password={$this->_credentials['password']}&advIdProgIdCombo=&tx_fmt=2&submit_tx=Download", array());
        $exportReport = $this->_client->get($urls);

        if (\preg_match("/DOCTYPE html PUBLIC/", $exportReport[0])) {
            $connection = false;
        }
        */
        return $connection;
    }

    /**
     * @param string $idSite
     */
    public function addAllowedSite(string $idSite){
        $this->_sitesAllowed[]=$idSite;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "Ebay";
        $obj['url'] = "https://publisher.ebaypartnernetwork.com";
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();

        $urls = array();

        $postParams = array(
            new \Oara\Curl\Parameter('username', $this->_credentials['user']),
            new \Oara\Curl\Parameter('password', $this->_credentials['password']),
            new \Oara\Curl\Parameter('isEnc', 'false'),
            new \Oara\Curl\Parameter('fileFormat', 'txt'),
            new \Oara\Curl\Parameter('eventType', 'earnings'),  // use 'all' to download all events
            new \Oara\Curl\Parameter('startPostDate', $dStartDate->format('Y-m-d')),
            new \Oara\Curl\Parameter('endPostDate', $dEndDate->format('Y-m-d')),
        );
        $url = new \Oara\Curl\Request("https://api.epn.ebay.com/rpt/events/v1/detail/tdr", $postParams );


        $urls[] = $url;
        $exportData = array();

        try {
            $exportReport = $this->_client->post($urls, 0);
            $exportData = \str_getcsv($exportReport[0], "\n");
        } catch (\Exception $e) {
            // ignore any error
        }

        // OLD Version - URL Doesn't work anymore - 2017-08-31 <PN>
        /*
        $urls = array();
        $urls[] = new \Oara\Curl\Request("https://publisher.ebaypartnernetwork.com/PublisherReportsTx?pt=2&start_date={$dStartDate->format("n/j/Y")}&end_date={$dEndDate->format("n/j/Y")}&user_name={$this->_credentials['user']}&user_password={$this->_credentials['password']}&advIdProgIdCombo=&tx_fmt=3&submit_tx=Download", array());
        $exportData = array();
        try {
            $exportReport = $this->_client->get($urls, 'content', 5);
            $exportData = \str_getcsv($exportReport[0], "\n");
        } catch (\Exception $e) {

        */
        $num = \count($exportData);
        for ($i = 1; $i < $num; $i++) {
            $transactionExportArray = \str_getcsv($exportData[$i], "\t");

            if ($transactionExportArray[2] == "Winning Bid (Revenue)" && (empty($this->_sitesAllowed) || \in_array($transactionExportArray[5], $this->_sitesAllowed))) {
                $transaction = Array();
                $transaction['merchantId'] = 0;
                $transaction['merchantName'] = '';
                $transaction['unique_id']  = $transactionExportArray[18];
                $transactionDate = \DateTime::createFromFormat("Y-m-d", $transactionExportArray[0]);
                $transaction['date'] = $transactionDate->format("Y-m-d") . ' 00:00:00';
                $postDate = \DateTime::createFromFormat("Y-m-d", $transactionExportArray[1]);
                $transaction['post_date'] = $postDate->format("Y-m-d") . ' 00:00:00';
                if ($transactionExportArray[10] != null) {
                    $transaction['custom_id'] = $transactionExportArray[10];
                }
                $transaction['click_date'] = $transactionExportArray[11];
                $transaction['amount'] = (float) $transactionExportArray[3];
                $transaction['commission'] = (float) $transactionExportArray[20];

                if ($transaction['amount'] < 0 && $transaction['commission'] < 0) {
                    $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                    $transaction['amount'] = abs($transaction['amount']);
                    $transaction['commission'] = abs($transaction['commission']);
                }
                else {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                }
                $totalTransactions[] = $transaction;
            }
        }

        // Step 2 - Group transactions by unique_id and sum um values to get the real amount/commission values
        $consolidate = Array();
        $num = \count($totalTransactions);
        for ($i = 1; $i < $num; $i++) {
            $unique_id = $totalTransactions[$i]['unique_id'];
            if (array_key_exists($unique_id, $consolidate)) {
                $consolidate[$unique_id]['amount'] += $totalTransactions[$i]['amount'];
                $consolidate[$unique_id]['commission'] += $totalTransactions[$i]['commission'];
                if ($totalTransactions[$i]['post_date'] > $consolidate[$unique_id]['post_date']) {
                    $consolidate[$unique_id]['post_date'] = $totalTransactions[$i]['post_date'];
                }
            }
            else {
                $consolidate[$unique_id] = $totalTransactions[$i];
            }
        }
        // Step 3 - Get total by unique id
        $totalTransactions = array();
        foreach ($consolidate as $unique_id => $transaction) {
            if ($transaction['amount'] <=0 && $transaction['commission'] <=0) {
                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
            }
            else {
                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            }
            $totalTransactions[] = $transaction;
        }
        return $totalTransactions;
    }
}
