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
    //$merchantList = $CommissionJunction->getMerchants();
    echo '<pre>';
    // var_dump($merchantList);
    echo '</pre>';

    /**
     *Sales list
     */

    echo '<h1>Sales</h1>';
    $merchantList = array(
        //'1124214' => array('cid' => '1124214', 'name' => 'zChocolat.com'),
        //'1911025' => array('cid' => '1911025', 'name' => 'Accorhotels.com Europe & ROW'),
        //'2143811' => array('cid' => '2143811', 'name' => 'Norton by Symantec - France'),
        //'3350997' => array('cid' => '3350997', 'name' => 'Kaspersky France'),
        //'2446763' => array('cid' => '2446763', 'name' => 'Abritel FR'),
        //'2683708' => array('cid' => '2683708', 'name' => 'LightInTheBox')
    );
    $sales = $CommissionJunction->getSales(new DateTime('2017-04-10'), new DateTime('2017-04-10'), $merchantList);
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
    //var_dump($deals);
    echo '</pre>';

    echo '<h1>Single deal merchant id = 2143811 (Norton by Symantec - France)</h1>';
    $deals = $CommissionJunction->getDeals(2143811);
    echo '<pre>';
    //var_dump($deals);
    echo '</pre>';

}
