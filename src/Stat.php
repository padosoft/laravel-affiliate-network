<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class Stat
 * @package Padosoft\AffiliateNetwork
 */
class Stat
{
    /**
     * @var string
     */
    public $currency = '';

    /**
     * @var string
     */
    public $date = '';

    /**
     * @var array
     */
    public $total = array();

    /**
     * @var array
     */
    public $open = array();

    /**
     * @var array
     */
    public $approved = array();

    /**
     * @var array
     */
    public $confirmed = array();

    /**
     * @var array
     */
    public $reject = array();

    /**
     * @method createInstance
     * @return obj istance
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new Stat();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance Stat - ' . $e->getMessage());
        }
        return $obj;
    }

}
