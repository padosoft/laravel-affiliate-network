<?php
/**
 * Copyright (c) Padosoft.com 2017.
 */
namespace Padosoft\AffiliateNetwork;

/**
 * Class Deal
 * @package Padosoft\AffiliateNetwork
 */
class DealsResultset
{
    public $page=0;
    public $items=0;
    public $total=0;
    public $deals=[];
    /**
     * @method createInstance
     * @return obj istance
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new DealsResultset();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance DealsResultset - ' . $e->getMessage());
        }
        return $obj;
    }
}