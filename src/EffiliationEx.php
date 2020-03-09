<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */
namespace Padosoft\AffiliateNetwork;
use Oara\Network\Publisher\Effiliation as EffiliationOara;

class EffiliationEx extends EffiliationOara{
    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        try {

            $totalTransactions = array();

            $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

            // Retrieve by date transaction instead of date click (type=datetran)- <PN> 2017-07-05
            $url = 'https://api.effiliation.com/apiv2/transaction.csv?key=' . $this->_credentials["apiPassword"] . '&start=' . $dStartDate->format("d/m/Y") . '&end=' . $dEndDate->format("d/m/Y") . '&type=datetran&all=yes&timestamp=' . time();
            // Set timeout to 300 secs. due to api delays - <PN> 2017-06-20
            $ctx = stream_context_create(array(
                    'http' => array(
                        'timeout' => 300
                    )
                )
            );
            // Log Debug info
            echo (New \DateTime())->format("d/m/Y H:i:s") . " - EffiliationEx getTransactionList from " . $dStartDate->format("d/m/Y") . " to " . $dEndDate->format("d/m/Y"),PHP_EOL;

            $content = \utf8_encode(\file_get_contents($url, 0, $ctx));
            $exportData = \str_getcsv($content, "\n");
            $num = \count($exportData);

            for ($i = 1; $i < $num; $i++) {
                $transactionExportArray = \str_getcsv($exportData[$i], "|");
                // We don't need to check whether the merchant id is valid or not ...
                // ... for old transactions may be expired and not included in current merchant list
                // <PN> 2017-06-22
                // if (isset($merchantIdList[(int)$transactionExportArray[2]])) {

                $transaction = Array();
                $merchantId = (int)$transactionExportArray[2];
                $transaction['merchantId'] = $merchantId;
                // Changed Transaction date index from 10 to 12 - 2018-01-01 <PN>
                $transaction['date'] = $transactionExportArray[12];
                $transaction["click_date"] = $transactionExportArray[11]; // Added <PN>
                $transaction['unique_id'] = $transactionExportArray[0];
                $transaction['custom_id'] = '';
                $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                if ($transactionExportArray[4] != null) {
                    $transaction['custom_id'] = $transactionExportArray[4];
                }

                switch ($transactionExportArray[9]) {
                    case 'Valide':
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                        break;
                    case 'Attente':
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        break;
                    case 'Refusé':
                    case 'Refuse':
                        // Handle both variations - <PN> - 2017-06-21
                        $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        break;
                    default:
                        // Invalid status
                        echo PHP_EOL."EffiliationEx - Invalid transaction status: " . $transactionExportArray[9] . " (transaction id = " . $transactionExportArray[0] . ")";
                        break;
                }
                $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[7]);
                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[8]);
                // Get absolute values - 2017-12-13 <PN>
                $transaction ['amount'] = \abs($transaction ['amount']);
                $transaction ['commission'] = \abs($transaction ['commission']);
                $totalTransactions[] = $transaction;
                // }
            }
        } catch (\Exception $e) {
            // Avoid lost of transactions if one date failed - <PN> - 2017-06-20
            echo PHP_EOL."EffiliationEx - getTransactionList err: ".$e->getMessage().PHP_EOL;
            throw new \Exception($e);
        }
        echo (New \DateTime())->format("d/m/Y H:i:s") . " - EffiliationEx getTransactionList - return " . count($totalTransactions) . " transactions" . PHP_EOL;
        return $totalTransactions;
    }
}
