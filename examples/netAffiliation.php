<?php
use Padosoft\AffiliateNetwork\Networks\NetAffiliation;

// Include config
include_once 'config.php';

$NetAffiliation = new NetAffiliation($_ENV['NETAFFILIATION_USERNAME'], $_ENV['NETAFFILIATION_PASSWORD']);
$isLogged = $NetAffiliation->checkLogin();
if($isLogged) {

    /**
     * Merchants List
     */

    echo '<h1>Merchants list</h1>';
    $merchantList = $NetAffiliation->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';

    /**
     *Sales list
     */

    echo '<h1>Sales</h1>';
    $merchantList = array(
        array('cid' => '40949', 'name' => 'Lentiamo IT')
    );
    $sales = $NetAffiliation->getSales(new DateTime('2016-11-01'), new DateTime('2016-11-01'), $merchantList);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';

    /**
     * Stats list
     */

    /*
    echo '<h1>Stats</h1>';
    $stats = $NetAffiliation->getStats(new DateTime('2016-10-14'), new DateTime('2016-11-15'));
    echo '<pre>';
    var_dump($stats);
    echo '</pre>';
    */

    /**
     * Deals
     */

    echo '<h1>Deals</h1>';
    $deals = $NetAffiliation->getDeals();
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';

    /*
    echo '<h1>Single deal merchant id = 7853</h1>';
    $deals = $NetAffiliation->getDeals(7853);
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';
    */


}
