<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */
namespace Padosoft\AffiliateNetwork;

use Oara\Network\Publisher\TradeDoubler as TradeDoublerOara;

class TradeDoublerEx extends TradeDoublerOara
{
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

        $totalTransactions = Array();
        $valuesFormExport = array(new \Oara\Curl\Parameter('reportName', 'aAffiliateEventBreakdownReport'),
            new \Oara\Curl\Parameter('columns', 'programId'),
            new \Oara\Curl\Parameter('columns', 'timeOfVisit'),
            new \Oara\Curl\Parameter('columns', 'timeOfEvent'),
            new \Oara\Curl\Parameter('columns', 'timeInSession'),
            new \Oara\Curl\Parameter('columns', 'lastModified'),
            new \Oara\Curl\Parameter('columns', 'epi1'),
            new \Oara\Curl\Parameter('columns', 'eventName'),
            new \Oara\Curl\Parameter('columns', 'pendingStatus'),
            new \Oara\Curl\Parameter('columns', 'siteName'),
            new \Oara\Curl\Parameter('columns', 'graphicalElementName'),
            new \Oara\Curl\Parameter('columns', 'graphicalElementId'),
            new \Oara\Curl\Parameter('columns', 'productName'),
            new \Oara\Curl\Parameter('columns', 'productNrOf'),
            new \Oara\Curl\Parameter('columns', 'productValue'),
            new \Oara\Curl\Parameter('columns', 'affiliateCommission'),
            new \Oara\Curl\Parameter('columns', 'link'),
            new \Oara\Curl\Parameter('columns', 'leadNR'),
            new \Oara\Curl\Parameter('columns', 'orderNR'),
            new \Oara\Curl\Parameter('columns', 'pendingReason'),
            new \Oara\Curl\Parameter('columns', 'orderValue'),
            new \Oara\Curl\Parameter('isPostBack', ''),
            new \Oara\Curl\Parameter('metric1.lastOperator', '/'),
            new \Oara\Curl\Parameter('interval', ''),
            new \Oara\Curl\Parameter('favoriteDescription', ''),
            new \Oara\Curl\Parameter('event_id', '0'),
            new \Oara\Curl\Parameter('pending_status', '1'),
            new \Oara\Curl\Parameter('run_as_organization_id', ''),
            new \Oara\Curl\Parameter('minRelativeIntervalStartTime', '0'),
            new \Oara\Curl\Parameter('includeWarningColumn', 'true'),
            new \Oara\Curl\Parameter('metric1.summaryType', 'NONE'),
            new \Oara\Curl\Parameter('metric1.operator1', '/'),
            new \Oara\Curl\Parameter('latestDayToExecute', '0'),
            new \Oara\Curl\Parameter('showAdvanced', 'true'),
            new \Oara\Curl\Parameter('breakdownOption', '1'),
            new \Oara\Curl\Parameter('metric1.midFactor', ''),
            new \Oara\Curl\Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEEVENTBREAKDOWNREPORT_TITLE'),
            new \Oara\Curl\Parameter('setColumns', 'true'),
            new \Oara\Curl\Parameter('metric1.columnName1', 'orderValue'),
            new \Oara\Curl\Parameter('metric1.columnName2', 'orderValue'),
            new \Oara\Curl\Parameter('reportPrograms', ''),
            new \Oara\Curl\Parameter('metric1.midOperator', '/'),
            new \Oara\Curl\Parameter('dateSelectionType', '2'),
            new \Oara\Curl\Parameter('favoriteName', ''),
            new \Oara\Curl\Parameter('affiliateId', ''),
            new \Oara\Curl\Parameter('dateType', '1'),
            new \Oara\Curl\Parameter('period', 'custom_period'),
            new \Oara\Curl\Parameter('tabMenuName', ''),
            new \Oara\Curl\Parameter('maxIntervalSize', '0'),
            new \Oara\Curl\Parameter('favoriteId', ''),
            new \Oara\Curl\Parameter('sortBy', 'timeOfEvent'),
            new \Oara\Curl\Parameter('metric1.name', ''),
            new \Oara\Curl\Parameter('customKeyMetricCount', '0'),
            new \Oara\Curl\Parameter('metric1.factor', ''),
            new \Oara\Curl\Parameter('showFavorite', 'false'),
            new \Oara\Curl\Parameter('separator', ','),
            new \Oara\Curl\Parameter('format', 'CSV')
        );
        $valuesFormExport[] = new \Oara\Curl\Parameter('startDate', self::formatDate($dStartDate));
        $valuesFormExport[] = new \Oara\Curl\Parameter('endDate', self::formatDate($dEndDate));
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);

        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        $exportData = \str_getcsv($exportReport[0], "\r\n");
        $num = \count($exportData);
        for ($i = 2; $i < $num - 1; $i++) {

            $transactionExportArray = \str_getcsv($exportData[$i], ",");

            if (!isset($transactionExportArray[2])) {
                throw new \Exception('Problem getting transaction\n\n');
            }
            if (\count($this->_sitesAllowed) == 0 || \in_array($transactionExportArray[13], $this->_sitesAllowed)) {


                if ($transactionExportArray[0] !== '' && isset($merchantIdList[(int)$transactionExportArray[2]])) {

                    $transaction = Array();
                    $transaction['merchantId'] = $transactionExportArray[2];
                    $transactionDate = self::toDate(\substr($transactionExportArray[4], 0, -6));

                    $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                    if ($transactionExportArray[8] != '') {
                        $transaction['unique_id'] = \substr($transactionExportArray[8], 0, 200);
                    } else
                        if ($transactionExportArray[7] != '') {
                            $transaction['unique_id'] = \substr($transactionExportArray[7], 0, 200);
                        } else {
                            throw new \Exception("No Identifier");
                        }


                    if ($transactionExportArray[9] != '') {
                        $transaction['custom_id'] = $transactionExportArray[9];
                    }

                    if ($transactionExportArray[11] == 'A') {
                        $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if ($transactionExportArray[11] == 'P') {
                            $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else
                            if ($transactionExportArray[11] == 'D') {
                                $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                            }

                    if ($transactionExportArray[19] != '') {
                        $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[19]);
                    } else {
                        $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[20]);
                    }

                    $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[20]);
                    $totalTransactions[] = $transaction;
                }
            }
        }
        $insertedTransactions=parent::getTransactionList($merchantList,$dStartDate,$dEndDate);
        foreach ($insertedTransactions as $transaction){
            if (array_key_exists('unique_id',$transaction)){
                $found=false;
                foreach ($totalTransactions as $transaction2){
                    if ($transaction2['unique_id']==$transaction['unique_id']){
                        $found=true;
                        break;
                    }
                }
                if (!$found){
                    $totalTransactions[]=$transaction;
                }
            }
        }
        return $totalTransactions;
    }
}