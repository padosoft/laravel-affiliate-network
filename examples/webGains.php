<?php
use Padosoft\AffiliateNetwork\Networks\WebGains;

// Include config
include_once 'config.php';

$WebGains = new WebGains($_ENV['WEBGAINS_USERNAME'], $_ENV['WEBGAINS_PASSWORD']);
$isLogged = $WebGains->checkLogin();
if($isLogged) {

    /**
     * Merchants List
     */

    echo '<h1>Merchants list</h1>';
    $merchantList = $WebGains->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';

    /**
     *Sales list
     */

    echo '<h1>Sales</h1>';
    $merchantList = array(
        array('cid' => '7075', 'name' => 'MyeFox.it')
    );
    $sales = $WebGains->getSales(new DateTime('2016-10-29'), new DateTime('2016-10-29'), $merchantList);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';

    /**
     * Stats list
     */

    /*
    echo '<h1>Stats</h1>';
    $stats = $WebGains->getStats(new DateTime('2016-10-14'), new DateTime('2016-11-15'));
    echo '<pre>';
    var_dump($stats);
    echo '</pre>';
    */

    /**
     * Deals
     */

    echo '<h1>Deals</h1>';
    $deals = $WebGains->getDeals();
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';

    /*
    echo '<h1>Single deal merchant id = 7853</h1>';
    $deals = $WebGains->getDeals(7853);
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';
    */

}
