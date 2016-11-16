<?php
use Padosoft\AffiliateNetwork\Networks\Zanox;

// Include config
include_once 'config.php';

$Zanox = new Zanox($_ENV['ZANOX_USERNAME'], $_ENV['ZANOX_PASSWORD']);
$isLogged = $Zanox->checkLogin();
if($isLogged) {
    /**
     * Merchants List
     */
    echo '<h1>Merchants list</h1>';
    $merchantList = $Zanox->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';
    /**
     *Sales list
     */
    echo '<h1>Sales</h1>';
    $merchantList = array(
        '11078' => array('cid' => '11078', 'name' => 'Yeppon IT'),
        '2725' => array('cid' => '2725', 'name' => 'Meridiana IT')
    );
    $sales = $Zanox->getSales(new DateTime('2016-10-17'), new DateTime('2016-11-15'), $merchantList);
    echo '<pre>';
    var_dump($sales);
    echo '</pre>';
    /**
     * Stats list
     */
    echo '<h1>Stats</h1>';
    $stats = $Zanox->getStats(new DateTime('2016-10-14'), new DateTime('2016-11-15'));
    echo '<pre>';
    var_dump($stats);
    echo '</pre>';

}
