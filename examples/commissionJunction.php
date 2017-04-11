<?php
use Padosoft\AffiliateNetwork\Networks\CommissionJunction;

// Include config
include_once 'config.php';

$CommissionJunction = new CommissionJunction($_ENV['COMMISSIONJUNCTION_USERNAME'], $_ENV['COMMISSIONJUNCTION_PASSWORD_API'], $_ENV['COMMISSIONJUNCTION_WEBSITEID']);
$isLogged = $CommissionJunction->checkLogin();
if($isLogged) {

    /**
     * Merchants List
     */

    echo '<h1>Merchants list</h1>';
    $merchantList = $CommissionJunction->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';

    /**
     *Sales list
     */

    echo '<h1>Sales</h1>';
    $merchantList = array(/*
        '11078' => array('cid' => '11078', 'name' => 'Yeppon IT'),
        '2725' => array('cid' => '2725', 'name' => 'Meridiana IT')
    */);
    $sales = $CommissionJunction->getSales(new DateTime('2017-04-01'), new DateTime('2017-04-02'), $merchantList);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';


    /**
     * Stats list
     */

    /*
    echo '<h1>Stats</h1>';
    $stats = $CommissionJunction->getStats(new DateTime('2016-10-14'), new DateTime('2016-11-15'));
    echo '<pre>';
    var_dump($stats);
    echo '</pre>';
    */

    /**
     * Deals
     */

    echo '<h1>Deals</h1>';
    $deals = $CommissionJunction->getDeals();
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';

    echo '<h1>Single deal merchant id = 3857130</h1>';
    $deals = $CommissionJunction->getDeals(3857130);
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';

}
