<?php
namespace Padosoft\AffiliateNetwork;

use Oara\Network\Publisher\AffiliNet as AffilinetOara;

class AffilinetEx extends AffilinetOara
{
    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     * @throws Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();

        // Don't need merchant list here
        // $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);
        $now = new \DateTime();

        if ($dEndDate->format('Y-m-d') == $now->format('Y-m-d')) {
            // Ends Today ... set current hour
            $dEndDate->setTime($now->format('h'), 0,0);
        }
        else {
            // End date in the past
            $dEndDate->setTime(23,59,59);
        }

        if (isset($_ENV['AFFILINET_CURRENCY'])) {
            // 2018-04-16 - <PN>
            $currency = $_ENV['AFFILINET_CURRENCY'];
        }
        else {
            $currency = 'EUR';
        }

        $step = 0;

        try {
            $publisherStatisticsServiceUrl = 'https://api.affili.net/V2.0/PublisherStatistics.svc?wsdl';
            $publisherStatisticsService = new \SoapClient($publisherStatisticsServiceUrl, array('compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE, 'soap_version' => SOAP_1_1));

            // Handle two steps: 1^ to get registered transactions / 2^ to get confirmed or cancelled transactions - <PN> 2017-06-27
            while ($step++ <= 2) {
                $params = array(
                    'StartDate' => \strtotime($dStartDate->format("Y-m-d 00:00:00")),
                    'EndDate' => \strtotime($dEndDate->format("Y-m-d h:i:s")),
                    'TransactionStatus' => 'All',
                    'ValuationType' => $step == 1 ? 'DateOfRegistration' : 'DateOfConfirmation'
                );
                $currentPage = 1;
                $transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);

                while (isset($transactionList->TotalRecords) && $transactionList->TotalRecords > 0 && isset($transactionList->TransactionCollection->Transaction)) {
                    $transactionCollection = array();
                    if (!\is_array($transactionList->TransactionCollection->Transaction)) {
                        $transactionCollection[] = $transactionList->TransactionCollection->Transaction;
                    } else {
                        $transactionCollection = $transactionList->TransactionCollection->Transaction;
                    }

                    foreach ($transactionCollection as $transactionObject) {
                        $uniqueId = $transactionObject->TransactionId;
                        if (!isset($totalTransactions[$uniqueId])) {
                            $transaction = array();
                            $transaction["status"] = $transactionObject->TransactionStatus;
                            $transaction["unique_id"] = $transactionObject->TransactionId;
                            $transaction["commission"] = $transactionObject->PublisherCommission;
                            $transaction["currency"] = $currency;   // 2018-04-16 - <PN>
                            $transaction["amount"] = $transactionObject->NetPrice;
                            $transaction["date"] = $transactionObject->RegistrationDate;
                            $transaction["click_date"] = $transactionObject->ClickDate;         // Future use - <PN>
                            $transaction["udpate_date"] = $transactionObject->CheckDate;        // Future use - <PN>
                            $transaction["merchantId"] = $transactionObject->ProgramId;
                            $transaction["custom_id"] = $transactionObject->SubId;
                            if ($transaction['status'] == 'Confirmed') {
                                $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                            } elseif ($transaction['status'] == 'Open') {
                                $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                            } elseif ($transaction['status'] == 'Cancelled') {
                                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                            }
                            $totalTransactions[$uniqueId] = $transaction;
                        }
                    }
                    $currentPage++;
                    $transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);
                }
            }
        } catch (\Exception $e) {
            // Avoid lost of transactions if one call failed
            echo PHP_EOL . "AffilinetEx - getTransactionList err: ".$e->getMessage().PHP_EOL;
            throw new \Exception($e);
        }
        echo (New \DateTime())->format("d/m/Y H:i:s") . " - AffilinetEx getTransactionList - return " . count($totalTransactions) . " transactions" . PHP_EOL;
        return $totalTransactions;
    }
}
