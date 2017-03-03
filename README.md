# Affiliate Networks API wrapper to provide common interface for affiliate networks publish API like Zanoz, Tradedoubler, Commission Junction etc...

[![Latest Version on Packagist](https://img.shields.io/packagist/v/padosoft/laravel-affiliate-network.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-affiliate-network)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/padosoft/laravel-affiliate-network/master.svg?style=flat-square)](https://travis-ci.org/padosoft/laravel-affiliate-network)
[![Quality Score](https://img.shields.io/scrutinizer/g/padosoft/laravel-affiliate-network.svg?style=flat-square)](https://scrutinizer-ci.com/g/padosoft/laravel-affiliate-network)
[![Total Downloads](https://img.shields.io/packagist/dt/padosoft/laravel-affiliate-network.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-affiliate-network)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/0008f1c1-34b2-4abd-8810-5bf5819ce45e.svg?style=flat-square)](https://insight.sensiolabs.com/projects/0008f1c1-34b2-4abd-8810-5bf5819ce45e)

The goal of this Laravel package is to wrap the Publisher Network Affiliate API like Zanox, Tradedoubler,  Commission Junction etc.. and provide simple methods to get deals and sales report and return a common interface for your use.

##Overview

Common methods are: 
- getDeals : get the network deals.
- getSales : get the network sales.
- getStats : get the network deals stats.
- getMerchants : get the network merchants.
- checkLogin : check if logged in network.
- login : login in into network.
- getTrackingParameter : get network tracking params.
- loadAvailableNetworks : get all available network.
- hasNetwork : check if network are available.
- addNetwork : add a network class that implements Network interface.

##Requires
  
- php: >=7.0.0
- illuminate/support
- padosoft/support
  
## Installation

You can install the package via composer:
```bash
$ composer require padosoft/laravel-affiliate-network
```

You must install this service provider.

``` php
// config/app.php
'provider' => [
    ...
    Padosoft\AffiliateNetwork\AffiliateNetworkServiceProvider::class,
    ...
];
```
You don't need to register the command in app/Console/Kernel.php, because it provides by AffiliateNetworkServiceProvider register() method.

You can publish the config file of this package with this command:
``` bash
php artisan vendor:publish --provider="Padosoft\AffiliateNetwork\AffiliateNetworkServiceProvider"
```
The following config file will be published in `config/laravel-affiliate-network.php`
``` php
return array(
    'zanox' => array(
        'username' => env(
            'ZANOX_USERNAME',
            'padosoft'
        ),
        'password' => env(
            'ZANOX_PASSWORD',
            ''
        )
    ),
    'tradedoubler' => array(
        'username' => env(
            'TRADEDOUBLER_USERNAME',
            'padosoft'
        ),
        'password' => env(
            'TRADEDOUBLER_PASSWORD',
            ''
        )
    ),
    'commissionjunction' => array(
        'username' => env(
            'COMMISSIONJUNCTION_USERNAME',
            'padosoft'
        ),
        'password' => env(
            'COMMISSIONJUNCTION_PASSWORD',
            ''
        )
    ),
);
```

In your app config folder you can copy from src/config/.env.example the settings for yours .env file used in laravel-affiliate-network.php.
If you use mathiasgrimm/laravel-env-validator 
in src/config folder you'll find an example for validate the env settings. 

## Networks Supported

- CommissionJunction
- Effiliation
- Netaffiliation
- Publicideas.com
- TradeDoubler
- Zanox
- WebGains


## Usage

Create new php file, add composer autoload and start using functions.

```php
<?php

require "vendor/autoload.php";

$arrDeals = NetworkManager::getDeals($dateFrom, $dateTo, $merchantID);
var_dump($arrDeals);//array of Deal

```
In Laravel:

```php

$networkManager=app(NetworkManager::class);

$dateFrom=new DateTime();
$dateTo= new DateTime();

//if you want to specify specific Merchant:
$arrMerchantID = array(
    array('cid' => '106818', 'name' => 'Spartoo.it')
);

$transactions = $networkManager->getSales('TradeDoubler',$dateFrom,$dateTo,$arrMerchantID);

```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email instead of using the issue tracker.

## Credits
- [Lorenzo Padovani](https://github.com/lopadova)
- [All Contributors](../../contributors)

## About Padosoft
Padosoft (https://www.padosoft.com) is a software house based in Florence, Italy. Specialized in E-commerce and web sites.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
