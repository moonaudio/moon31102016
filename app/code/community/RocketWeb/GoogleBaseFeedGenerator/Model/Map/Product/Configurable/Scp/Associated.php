<?php

/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   RocketWeb_GoogleBaseFeedGenerator
 * @copyright Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable_Scp_Associated extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract_Associated
{
    protected $_isScpAssociated = true;

    /**
     * Computes prices for given or current product.
     * It returns an array of 4 prices: price and special_price, buth including and excluding tax
     *
     * @return mixed
     */
    public function getPrices()
    {
        return parent::getPrices();
    }

    /**
     * @param array $params
     * @return string
     */
    public function mapDirectivePrice($params = array())
    {
        $prices = $this->getPrices();
        $includingTax = array_key_exists('param', $params['map']) ? (boolean)$params['map']['param'] : true;
        $price = $includingTax ? $prices['p_incl_tax'] : $prices['p_excl_tax'];

        /** @var Mage_Catalog_Model_Product $product */
        $product = $this->getProduct();

        // equivalent to default/template/catalog/product/msrp_price.phtml
        if ($this->_getHelper()->hasMsrp($product)){
            $qtyIncrements = $this->_getHelper()->getQuantityIcrements($product);
            $price = $this->convertPrice($product->getMsrp() * $qtyIncrements);
        }

        return ($price > 0) ? sprintf("%.2F", $price). ' '. $this->getData('store_currency_code') : '';
    }

    /**
     * Note: Magento takes the sale price from parent if it's a configurable.
     *
     * @param  array $params
     * @return string
     */
    protected function mapDirectiveSalePriceEffectiveDate($params = array())
    {
        if (array_key_exists('force_assoc', $params) && $params['force_assoc']) {
            return parent::mapDirectiveSalePriceEffectiveDate($params);
        }
        $cell = $this->getParentMap()->mapDirectiveSalePriceEffectiveDate($params);

        // something's going wrong, if sale price is set, date should not be empty
        if (empty($cell) && $this->hasSpecialPrice()) {
            return parent::mapDirectiveSalePriceEffectiveDate($params);
        }

        if (!empty($cell)) {
            $this->_findAndReplace($cell, $params['map']['column']);
        }
        return $cell;
    }
}
