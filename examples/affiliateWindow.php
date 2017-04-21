<?php
/*
http://wiki.awin.com/index.php/Advertiser_API

https://ui.awin.com/awin-api
Codice OAuth2
7d650200-2284-440c-b4db-9d1db865f6d4

Per ottenere le tranactions:
http://wiki.awin.com/index.php/API_get_transactions_list
https://api.awin.com/publishers/45628/transactions/?accessToken=7d650200-2284-440c-b4db-9d1db865f6d4&startDate=2017-02-20T00%3A00%3A00&endDate=2017-02-21T01%3A59%3A59&timezone=UTC


timezone=Europe/Berlin ?????
 */
use Padosoft\AffiliateNetwork\Networks\AffiliateWindow;

// Include config
include_once 'config.php';

$AffiliateWindowEx = new AffiliateWindow($_ENV['AFFILIATEWINDOW_API_USERNAME'], $_ENV['AFFILIATEWINDOW_API_PASSWORD']);
$isLogged = $AffiliateWindowEx->checkLogin();
if($isLogged) {
    /**
     * Merchants List
     */

/*
    echo '<h1>Merchants list</h1>';
    $merchantList = $AffiliateWindowEx->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';
*/

    /**
     *Sales list
     */


    echo '<h1>Sales</h1>';
    $merchantList = array(
        //'11078' => array('cid' => '11078', 'name' => 'Yeppon IT'),
        //'2725' => array('cid' => '2725', 'name' => 'Meridiana IT')
    );
    $sales = $AffiliateWindowEx->getSales(new DateTime('2017-02-20 12:30:00'), new DateTime('2017-02-21 14:15:00'), $merchantList);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';


    /**
     * Stats list
     */

    /*
    echo '<h1>Stats</h1>';
    $stats = $Zanox->getStats(new DateTime('2016-10-14'), new DateTime('2016-11-15'));
    echo '<pre>';
    var_dump($stats);
    echo '</pre>';
    */

    /**
     * Deals
     */
/*
    echo '<h1>Deals</h1>';
    $deals = $Zanox->getDeals();
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';

    echo '<h1>Single deal merchant id = 7853</h1>';
    $deals = $Zanox->getDeals(7853);
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';
*/

} else echo "login failed";
