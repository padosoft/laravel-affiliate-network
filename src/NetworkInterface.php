<?php

namespace Padosoft\AffiliateNetwork;

/**
 * Interface NetworkInterface
 * @package Padosoft\AffiliateNetwork
 */
interface NetworkInterface
{
    /**
     * @param int|null $merchantID
     * @param int $page
     * @param int $items_per_page
     *
     * @return DealsResultset
     */
    public function getDeals($merchantID,int $page=0,int $items_per_page=10 ) : DealsResultset;

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Transaction
     */
    public function getSales(\DateTime $dateFrom, \DateTime $dateTo, array $arrMerchantID = array()) : array;

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int $merchantID
     * @return array of Stat
     */
    public function getStats(\DateTime $dateFrom, \DateTime $dateTo, int $merchantID = 0) : array ;


    /**
     * @param  array $params  this array can contains these keys
     *                        string      query          search string
     *                        string      searchType     search type (optional) (contextual or phrase)
     *                        string      region         limit search to region (optional)
     *                        int         categoryId     limit search to categorys (optional)
     *                        array       programId      limit search to program list of programs (optional)
     *                        boolean     hasImages      products with images (optional)
     *                        float       minPrice       minimum price (optional)
     *                        float       maxPrice       maximum price (optional)
     *                        int         adspaceId      adspace id (optional)
     *                        int         page           page of result set (optional)
     *                        int         items          items per page (optional)
     *
     * @return ProductsResultset
     */
    public function getProducts(array $params = []) : ProductsResultset;

}
