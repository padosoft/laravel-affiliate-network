<?php
use Padosoft\AffiliateNetwork\Networks\Publicideas;

// Include config
include_once 'config.php';

$Publicideas = new Publicideas($_ENV['PUBLICIDEAS_USERNAME'], $_ENV['PUBLICIDEAS_PASSWORD']);
$isLogged = $Publicideas->checkLogin();
if($isLogged) {

    /**
     * Merchants List
     */

    echo '<h1>Merchants list</h1>';
    $merchantList = $Publicideas->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';

    /**
     *Sales list
     */

    echo '<h1>Sales</h1>';
    $merchantList = array(
        array('cid' => '1', 'name' => 'Publicidees')
    );
    $sales = $Publicideas->getSales(new DateTime('2016-10-17'), new DateTime('2016-11-15'), $merchantList);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';

    /**
     * Stats list
     */

    /*
    echo '<h1>Stats</h1>';
    $stats = $Publicideas->getStats(new DateTime('2016-10-14'), new DateTime('2016-11-15'));
    echo '<pre>';
    var_dump($stats);
    echo '</pre>';
    */

    /**
     * Deals
     */

    /*
    echo '<h1>Deals</h1>';
    $deals = $Publicideas->getDeals();
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';

    echo '<h1>Single deal merchant id = 7853</h1>';
    $deals = $Publicideas->getDeals(7853);
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';
    */

}
