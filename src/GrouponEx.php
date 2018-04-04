<?php
/**
 * Copyright (c) Padosoft.com 2017.
 * Created by Paolo Nardini - 2018-03-02
 */
namespace Padosoft\AffiliateNetwork;

use Oara\Network\Publisher\Groupon as GrouponOara;

class GrouponEx extends GrouponOara
{
    protected $_merchantIdList = array();     // To avoid repeated calls to \Oara\Utilities::getMerchantIdMapFromMerchantList
    protected $_countryIsoCode;               // Iso code of country to filter transactions

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);
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
        // Groupon don't need to check connection
        $connection = true;
        return $connection;
    }

    /**
     * @param string $idSite
     */
    public function addAllowedSite(string $idSite){
        $this->_sitesAllowed[] = $idSite;
    }

    /**
     * @param string $countryIsoCode
     */
    public function addCountry(string $countryIsoCode){
        $this->_countryIsoCode = $countryIsoCode;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "Groupon";
        $obj['url'] = "";
        $merchants[] = $obj;

        return $merchants;
    }


    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     * @throws \Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();
        $auxDate = clone $dStartDate;
        $amountDays = $dStartDate->diff($dEndDate)->days;
        for ($j = 0; $j < $amountDays; $j++) {

            // Getting the csv by curl can throw an exception if the csv size is 0 bytes. So, first of all, get the json. If total is 0, continue, else, get the csv.
            $valuesFormExport = array();
            $url = "https://partner-int-api.groupon.com/reporting/v2/order.json?clientId={$this->_credentials['apipassword']}&group=order&date={$auxDate->format("Y-m-d")}";
            if (!empty($this->_countryIsoCode)) {
                $url .= '&order.country=' . $this->_countryIsoCode;
            }
            $urls = array();
            $urls[] = new \Oara\Curl\Request($url, $valuesFormExport);
            $exportReport = $this->_client->get($urls);
            $jsonExportReport = json_decode($exportReport[0], true);

            if ($jsonExportReport['total'] != 0) {

                $valuesFormExport = array();
                $url = "https://partner-int-api.groupon.com/reporting/v2/order.csv?clientId={$this->_credentials['apipassword']}&group=order&date={$auxDate->format("Y-m-d")}";
                if (!empty($this->_countryIsoCode)) {
                    $url .= '&order.country=' . $this->_countryIsoCode;
                }
                $urls = array();
                $urls[] = new \Oara\Curl\Request($url, $valuesFormExport);
                $exportReport = $this->_client->get($urls);
                $exportData = \str_getcsv($exportReport[0], "\n");
                $num = \count($exportData);
                for ($i = 1; $i < $num; $i++) {
                    $transactionExportArray = \str_getcsv($exportData[$i], ",");
                    $transaction = Array();
                    $transaction['merchantId'] = "1";
                    $transaction['date'] = $auxDate->format("Y-m-d H:i:s");
                    $transaction['unique_id'] = $transactionExportArray[0];
                    $transaction['currency'] = $transactionExportArray[4];

                    if ($transactionExportArray[1] != null) {
                        $transaction['custom_id'] = $transactionExportArray[1];
                    }

                    if ($transactionExportArray[5] == 'VALID') {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else if ($transactionExportArray[5] == 'INVALID' || $transactionExportArray[5] == 'REFUNDED') {
                        $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                    } else {
                        throw new \Exception("Status {$transactionExportArray[5]} unknown");
                    }

                    $transaction['amount'] = \Oara\Utilities::parseDouble((double)$transactionExportArray[8]);
                    $transaction['commission'] = \Oara\Utilities::parseDouble((double)$transactionExportArray[12]);
                    $totalTransactions[] = $transaction;
                }
            }
            $auxDate->add(new \DateInterval('P1D'));
        }

        return $totalTransactions;
    }

}
