<?php
/**
 * Copyright (c) Padosoft.com 2017.
 * Created by Paolo Nardini - 2018-03-02
 */
namespace Padosoft\AffiliateNetwork;

use Oara\Network\Publisher\Groupon as GrouponOara;

class GrouponEx extends GrouponOara
{
    protected $_serverNumber = 6;
    protected $_merchantIdList = array();     // To avoid repeated calls to \Oara\Utilities::getMerchantIdMapFromMerchantList

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "User Log in";
        $parameter["required"] = true;
        $parameter["name"] = "User";
        $credentials["user"] = $parameter;

        $parameter = array();
        $parameter["description"] = "Password to Log in";
        $parameter["required"] = true;
        $parameter["name"] = "Password";
        $credentials["password"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        // Groupon don't need to check connection
        $connection = true;
        return $connection;
    }

    /**
     * @param string $idSite
     */
    public function addAllowedSite(string $idSite){
        $this->_sitesAllowed[] = $idSite;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "Groupon";
        $obj['url'] = "";
        $merchants[] = $obj;

        return $merchants;
    }
}
