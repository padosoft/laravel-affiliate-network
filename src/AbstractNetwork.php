<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class AbstractNetwork
 * @package Padosoft\AffiliateNetwork
 */
abstract class AbstractNetwork
{
    /**
     * username
     * @var string
     */
    protected $username = '';

    /**
     * password
     * @var string
     */
    protected $password = '';

    /**
     * AbstractNetwork constructor.
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

}
