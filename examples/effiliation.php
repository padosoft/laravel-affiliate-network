<?php
use Padosoft\AffiliateNetwork\Networks\Effiliation;

// Include config
include_once 'config.php';

$Effiliation = new Effiliation($_ENV['EFFILIATION_PASSWORD'],$_ENV['EFFILIATION_PASSWORD'],$_ENV['EFFILIATION_PASSWORD']);
$isLogged = $Effiliation->checkLogin();
if($isLogged) {

    /**
     * Merchants List
     */
/*
    echo '<h1>Merchants list</h1>';
    $merchantList = $Effiliation->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';
*/

    echo '<h1>Sales</h1>';
    $merchantList = array(
        '315012245' => array('cid' => '315012245', 'name' => 'Boulanger '),
    );
    $sales = $Effiliation->getSales(new DateTime('2017-03-25'), new DateTime('2017-04-17'), $merchantList);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';

    /**
     * Stats list
     */

    /*
    echo '<h1>Stats</h1>';
    $stats = $Effiliation->getStats(new DateTime('2016-10-14'), new DateTime('2016-11-15'));
    echo '<pre>';
    var_dump($stats);
    echo '</pre>';
    */

    /**
     * Deals
     */
/*
    echo '<h1>Deals</h1>';
    $deals = $Effiliation->getDeals();
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';

    echo '<h1>Single deal merchant id = 315012692</h1>';
    $deals = $Effiliation->getDeals(315012692);
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';
*/
}
