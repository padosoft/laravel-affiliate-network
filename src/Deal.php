<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Class Deal
 * @package Padosoft\AffiliateNetwork
 */
class Deal
{
    /**
     * @var int
     */
    public $merchant_ID = 0;

    /**
     * @var string
     */
    public $merchant_name = '';

    /**
     * @var int
     */
    public $network_ID = 0;

    /**
     * @var int
     */
    public $type = 0;

    /**
     * @var string
     */
    public $code = '';

    /**
     * @var string
     */
    public $url = '';

    /**
     * @var \DateTime
     */
    public $start_date;

    /**
     * @var \DateTime
     */
    public $end_date;

    /**
     * @var \DateTime
     */
    public $update_date;

    /**
     * @var \DateTime
     */
    public $publish_start_date;

    /**
     * @var \DateTime
     */
    public $publish_end_date;

    /**
     * @var integer
     */
    public $deal_ID = 0;

    /**
     * @var string
     */
    public $currency_initial  = '';

    /**
     * @var string
     */
    public $language = '';

    /**
     * @var string
     */
    public $logo_path = '';

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var string
     */
    public $short_description = '';

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    public $information = '';

    /**
     * @var string
     */
    public $deal_type = '';

    /**
     * @var string
     */
    public $default_track_uri = '';

    /**
     * @var string
     */
    public $discount_amount='';

    /**
     * @var boolean
     */
    public $is_percentage = false;

    /**
     * @var string
     */
    public $minimum_order_value='';

    /**
     * @var boolean
     */
    public $is_exclusive = false;

    /**
     * @var boolean
     */
    public $is_site_specific = false;

    /**
     * @var string
     */
    public $landing_url = '';

    /**
     * @var string
     */
    public $ppv = '';

    /**
     * @var string
     */
    public $ppc = '';

    public function __construct() {
        $this->publish_end_date = $this->publish_start_date = $this->update_date = $this->end_date = $this->start_date = \DateTime::createFromFormat('Y-m-d','2000-01-01 00:00:00');
    }

    /**
     * @method createInstance
     * @return obj istance
     */
    public static function createInstance()
    {
        $obj = null;
        try {
            $obj = new Deal();
        } catch (\Exception $e) {
            throw new \Exception('Error creating instance Deal - ' . $e->getMessage());
        }
        return $obj;
    }

    /**
     * Move data from array to class by associative array source => destination
     * (skip invalid or empty fields)
     * @param array $source
     * @param array $association
     */
    public function setValues(array $source, array $association) {
        foreach ($association as $src => $dest) {
            if (array_key_exists($src, $source) && isset($this->$dest)) {
                if (strpos($dest, 'date') !== false) {
                    $this->$dest = $this->convertDate($source[$src]);
                }
                elseif (strpos($dest, 'is_') !== false) {
                    $this->$dest = $this->convertBool($source[$src]);

                }
                else {
                    $this->$dest = $source[$src];
                }
            }
            else {
                // Missing field .. ignore
            }
        }
    }

    public function convertDate($source) {
        // Try to convert any possible date format
        $date = \DateTime::createFromFormat(\DateTime::ISO8601, $source);
        if ($date === false) {
            $date = \DateTime::createFromFormat("Y-m-d\TH:i:s.uO", $source);
        }
        if ($date === false) {
            $date = \DateTime::createFromFormat("Y-m-d\TH:i:s", substr($source,0,19));
        }
        if ($date === false) {
            $date = \DateTime::createFromFormat("d/m/Y H:i:s", $source);
        }
        if ($date === false) {
            $date = \DateTime::createFromFormat('Y-m-d','2000-01-01 00:00:00');
        }
        return $date;
    }

    public function convertBool($source) {
        // Try to convert source to boolean
        $source = strtolower($source);
        if ($source == 'yes' || $source == 'true' || $source == '1' || $source == 'oui') {
            $value = true;
        }
        elseif ($source == 'no' || $source == 'false' || $source == '0' || $source == 'non') {
            $value = false;
        }
        else {
            $value = null;
        }
        return $value;
    }
}
