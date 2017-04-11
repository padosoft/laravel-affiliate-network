<?php
/**
 * Created by PhpStorm.
 * User: luna
 * Date: 07/04/17
 * Time: 16:57
 */

namespace Padosoft\AffiliateNetwork;


class ProductsResultset
{

    public $page=0;
    public $items=0;
    public $total=0;
    public $products=[];
    /**
     * @method createInstance
     * @return obj istance
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new ProductsResultset();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance ProductsResultset - ' . $e->getMessage());
        }
        return $obj;
    }
}