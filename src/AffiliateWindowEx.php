<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */
namespace Padosoft\AffiliateNetwork;

use Oara\Network\Publisher\AffiliateWindow as AffiliateWindowOara;

class AffiliateWindowEx extends AffiliateWindowOara
{
    /*
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = array();

        $id = $this->_credentials["accountid"];
        $pwd = $this->_credentials["apipassword"];
        echo "<br>end ";//." id ".$id." pwd ".$pwd."<br>";
        var_dump($dEndDate);

        $dStartDate_=$dStartDate->format("Y-m-d");
        //echo "<br>s date ".$dStartDate_;
        $dStartTime_=$dStartDate->format("H:s:i");
        $dEndDate_=$dEndDate->format("Y-m-d");
        $dEndTime_=$dEndDate->format("H:s:i");
        $dEndDate = urlencode($dEndDate_."T".$dEndTime_);
        $dStartDate = urlencode($dStartDate_."T".$dStartTime_);
        echo "<br>start date ".$dStartDate;
        //$url = 'https://api.awin.com/publishers/'.$id.'/transactions/?accessToken='.$pwd.'&startDate=2017-02-20T00%3A00%3A00&endDate=2017-02-21T01%3A59%3A59&timezone=Europe/Berlin';

        $url = 'https://api.awin.com/publishers/'.$id.'/transactions/?accessToken='.$pwd.'&startDate='.$dStartDate.'&endDate='.$dEndDate.'&timezone=Europe/Berlin';
        $content = \utf8_encode(\file_get_contents($url));
        $transactions = \json_decode($content);
        //var_dump($transactions);
        foreach ($transactions as $transaction)  {
            $myTransaction = Array();
            $myTransaction['merchantId'] = $transaction->advertiserId;
            $myTransaction['date'] = $transaction->transactionDate;
            $myTransaction['unique_id'] = $transaction->id;
            $myTransaction['custom_id'] = $transaction->paymentId;

            if ($transaction->commissionStatus == 'approved') {
                $myTransaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            } else if ($transaction->commissionStatus == 'pending') {
                $myTransaction['status'] = \Oara\Utilities::STATUS_PENDING;
            } else if ($transaction->commissionStatus == 'pending') {
                $myTransaction['status'] = \Oara\Utilities::STATUS_DECLINED;
            }
            //echo $transaction->saleAmount->amount."<br>";
            $myTransaction['amount'] = \Oara\Utilities::parseDouble($transaction->saleAmount->amount);
            $myTransaction['commission'] = \Oara\Utilities::parseDouble($transaction->commissionAmount->amount);
            $totalTransactions[] = $myTransaction;
        }
        return $totalTransactions;
    }*/
}