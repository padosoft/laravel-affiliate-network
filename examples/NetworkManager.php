<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */
use Padosoft\AffiliateNetwork\NetworkManager;
if (!function_exists('public_path')){
    function public_path(){
        return dirname(__FILE__);
    }
}

include_once 'config.php';
$objNetworkManager= new NetworkManager();
$objNetworkManager->login('Zanox',$_ENV['ZANOX_USERNAME'], $_ENV['ZANOX_PASSWORD']);
$isLogged = $objNetworkManager->checkLogin('Zanox');
if ($isLogged){
    echo '<h1>Merchants list</h1>';
    $merchantList = $objNetworkManager->getDeals('Zanox');
    echo '<pre>';
    var_dump($merchantList);
    echo '</pre>';
}