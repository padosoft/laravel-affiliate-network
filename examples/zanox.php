<?php
use Padosoft\AffiliateNetwork\Networks\Zanox;

// Include config
include_once 'config.php';

$Zanox = new Zanox($_ENV['ZANOX_USERNAME'], $_ENV['ZANOX_PASSWORD']);
$isLogged = $Zanox->checkLogin();
if($isLogged) {
    $merchantList = $Zanox->getMerchants();
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';
}
