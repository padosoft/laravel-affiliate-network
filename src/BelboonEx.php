<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */
namespace Padosoft\AffiliateNetwork;
use Oara\Network\Publisher\Belboon as BelboonOara;

class BelboonEx extends BelboonOara
{
    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['apipassword'];
        $this->_platformList = array(['id' => $credentials['id_site']]);

        $this->_client = new \SoapClient('http://api.belboon.com/?wsdl', array('login' => $user, 'password' => $password, 'trace' => true));
        $this->_client->getAccountInfo();

        return true;
    }


}