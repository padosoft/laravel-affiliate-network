<?php
/**
 * Created by PhpStorm.
 * User: luna
 * Date: 07/04/17
 * Time: 17:01
 */

namespace Padosoft\AffiliateNetwork;


class Product
{
    /**
     * @var int
     */
    public $merchant_ID=0;

    /**
     * @var string
     */
    public $merchant_name='';

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $modified = '';

    /**
     * @var double
     */
    public $price = 0.0;

    /**
     * @var string
     */
    public $currency = '';

    /**
     * @var string
     */
    public $ppv = '';

    /**
     * @var string
     */
    public $ppc = '';

    //adspaceId = 0;

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    public $manufacturer = '';

    /**
     * @var string
     */
    public $ean = '';

    /**
     * @var string
     */
    public $deliveryTime = '';

    /**
     * @var double
     */
    public $priceOld = 0.0;

    /**
     * @var string
     */
    public $shippingCosts = '';

    /**
     * @var string
     */
    public $shipping = '';

    /**
     * @var string
     */
    public $merchantCategory;

    /**
     * @var string
     */
    public $merchantProductId = '';

    /**
     * @var string
     */
    public $id = '';

    /**
     * @var string
     */
    public $image = '';

    /**
     * @method createInstance
     * @return obj istance
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new Product();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance Deal - ' . $e->getMessage());
        }
        return $obj;
    }
}
