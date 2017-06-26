<?php
namespace Padosoft\AffiliateNetwork;

use Oara\Network\Publisher\Affilinet as AffilinetOara;

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

        try {
            $publisherStatisticsServiceUrl = 'https://api.affili.net/V2.0/PublisherStatistics.svc?wsdl';
            $publisherStatisticsService = new \SoapClient($publisherStatisticsServiceUrl, array('compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE, 'soap_version' => SOAP_1_1));

            $params = array(
                'StartDate' => \strtotime($dStartDate->format("Y-m-d")),
                'EndDate' => \strtotime($dEndDate->format("Y-m-d")),
                'TransactionStatus' => 'All',
                'ValuationType' => 'DateOfConfirmation'     // Only modified transactions within date range - <PN> - 2017-06-26
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

                    $transaction = array();
                    $transaction["status"] = $transactionObject->TransactionStatus;
                    $transaction["unique_id"] = $transactionObject->TransactionId;
                    $transaction["commission"] = $transactionObject->PublisherCommission;
                    $transaction["amount"] = $transactionObject->NetPrice;
                    $transaction["date"] = $transactionObject->RegistrationDate;
                    $transaction["click_date"] = $transactionObject->ClickDate;         // Future use - <PN>
                    $transaction["udpate_date"] = $transactionObject->CheckDate;        // Future use - <PN>
                    $transaction["merchantId"] = $transactionObject->ProgramId;
                    $transaction["custom_id"] = $transactionObject->SubId;
                    if ($transaction['status'] == 'Confirmed') {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if ($transaction['status'] == 'Open') {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else
                            if ($transaction['status'] == 'Cancelled') {
                                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                            }
                    $totalTransactions[] = $transaction;
                }
                $currentPage++;
                $transactionList = self::affilinetCall('transaction', $publisherStatisticsService, $params, 0, $currentPage);
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
