<?php

namespace Padosoft\AffiliateNetwork\Networks;

use Illuminate\Support\Facades\Facade;

class NetworkManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'networkmanager';
    }
}
