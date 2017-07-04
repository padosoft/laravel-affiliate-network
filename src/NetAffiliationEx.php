<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */


namespace Padosoft\AffiliateNetwork;
use Oara\Network\Publisher\NetAffiliation as NetAffiliationOara;

class NetAffiliationEx extends NetAffiliationOara
{
    protected $_serverNumber = 6;
    protected $_merchantIdList = array();     // To avoid repeated calls to \Oara\Utilities::getMerchantIdMapFromMerchantList

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object,$methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Call protected/private property of a class.
     * @param $object
     * @param $propertyName
     *
     * @return mixed
     */
    public function invokeProperty(&$object,$propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    /**
     * @param $credentials
     * @throws \Exception
     * @throws \Oara\Curl\Exception
     */
    /*public function login($credentials){
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);

    }*/

    /**
     * @return bool
     */
    /*public function checkConnection()
    {
        $connection = false;

        try{
            $valuesFormExport[] = new \Oara\Curl\Parameter('authl', $this->_credentials["user"]);
            $valuesFormExport[] = new \Oara\Curl\Parameter('authv', $this->_credentials["apiPassword"]);
            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://stat.netaffiliation.com/requete.php?', $valuesFormExport);

            $exportReport = $this->_client->get($urls);
            $exportData = str_getcsv($exportReport[0], "\n");
            if (substr($exportData[0],0,2)=='OK'){
                $connection=true;
            }
        }catch (\Exception $exception){

        }finally{

        }


        return $connection;
    }*/
    /**
     * @param string $idSite
     */
    public function addAllowedSite(string $idSite){
        $this->_sitesAllowed[]=$idSite;
    }
    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     * @throws Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        try {
            $totalTransactions = array();
            if (count($this->_merchantIdList) == 0) {
                $this->_merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);
            }

            $valuesFormExport = array();
            $valuesFormExport[] = new \Oara\Curl\Parameter('authl', $this->_credentials["user"]);
            $valuesFormExport[] = new \Oara\Curl\Parameter('authv', $this->_credentials["apiPassword"]);
            $valuesFormExport[] = new \Oara\Curl\Parameter('champs', 'idcampagne,date,etat,argsite,montant,gains,monnaie,idsite,id');
            $valuesFormExport[] = new \Oara\Curl\Parameter('debut', $dStartDate->format("Y-m-d"));
            $valuesFormExport[] = new \Oara\Curl\Parameter('fin', $dEndDate->format("Y-m-d"));
            $urls = array();
            $urls[] = new \Oara\Curl\Request('https://stat.netaffiliation.com/requete.php?', $valuesFormExport);

            $exportReport = $this->_client->get($urls);



            //sales
            $exportData = str_getcsv($exportReport[0], "\n");
            $num = count($exportData);
            for ($i = 1; $i < $num; $i++) {
                $transactionExportArray = str_getcsv($exportData[$i], ";");
                if (\count($this->_sitesAllowed) == 0 || \in_array($transactionExportArray[7], $this->_sitesAllowed)) {
                    if (count($this->_merchantIdList) < 1 || isset($this->_merchantIdList[$transactionExportArray[0]])) {
                        // Ignore missing merchants ID
                        // echo "NetAffiliationEx - getTransactionList - Merchant Id " . $transactionExportArray[0] . " not found " . PHP_EOL;
                    }
                    $transaction = Array();
                    $transaction['merchantId'] = $transactionExportArray[0];
                    //$transactionDate = \DateTime::createFromFormat("d/m/Y H:i:s", $transactionExportArray[1]);
                    $transaction['date'] = $transactionExportArray[1];
                    $transaction['title'] = '';

                    if ($transactionExportArray[3] != null) {
                        $transaction['custom_id'] = $transactionExportArray[3];
                    }

                    $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    if (\strstr($transactionExportArray[2], 'v')) {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if (\strstr($transactionExportArray[2], 'r')) {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        } else if (\strstr($transactionExportArray[2], 'a')) {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else {
                            throw new \Exception ("Status not found");
                        }
                    $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[4]);
                    $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[5]);

                    $transaction['currency'] = $transactionExportArray[6];
                    $transaction['unique_id'] = $transactionExportArray[8];
                    // Create the unique transaction id by combining id+id_campagne - <PN> - 2017-07-04
                    $transaction['transaction_id'] = $transactionExportArray[8] . '-' . $transactionExportArray[0];

                    $totalTransactions[] = $transaction;
                }
            }
        } catch (\Exception $e) {
            //echo "stepE ";
            echo PHP_EOL."NetAffiliationEx - getTransactionList err: ".$e->getMessage().PHP_EOL;
            //var_dump($e->getTraceAsString());
            throw new \Exception($e);
        }
        return $totalTransactions;
    }
}