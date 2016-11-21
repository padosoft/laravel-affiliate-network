<?php
use Padosoft\AffiliateNetwork\Networks\TradeDoubler;

// Include config
include_once 'config.php';

$TradeDoubler = new TradeDoubler($_ENV['TRADEDOUBLER_USERNAME'], $_ENV['TRADEDOUBLER_PASSWORD']);
$isLogged = $TradeDoubler->checkLogin();

if($isLogged) {
    /**
     * Merchants List
     */
    echo '<h1>Merchants list</h1>';
    $merchantList = $TradeDoubler->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';

    /**
     * Sales list
     */
    echo '<h1>Sales</h1>';
    $merchantList = array(
        array('cid' => '106818', 'name' => 'Spartoo.it')
    );
    $sales = $TradeDoubler->getSales(new DateTime('2016-10-17'), new DateTime('2016-11-15'), $merchantList);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';

    /**
     * Deals
     */

    echo '<h1>Deals</h1>';
    $deals = $TradeDoubler->getDeals();
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';

    echo '<h1>Single deal merchant id = 258805</h1>';
    $deals = $TradeDoubler->getDeals(258805);
    echo '<pre>';
    var_dump($deals);
    echo '</pre>';
}
