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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable_Scp extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable
{

    /**
     * Computes prices for given or current product.
     * It returns an array of 4 prices: price and special_price, buth including and excluding tax
     *
     * @return mixed
     */
    public function getPrices()
    {
        $minPrice = PHP_INT_MAX;
        $prices = array();

        foreach ($this->getAssocMaps() as $assocMap) {
            $tmpPrices = $assocMap->getPrices();
            $price = min($tmpPrices['p_excl_tax'], $tmpPrices['sp_excl_tax']);
            if ($price < $minPrice) {
                $minPrice = $price;
                $prices = $tmpPrices;
            }
        }

        // Fallback in case there isn't any associated products
        if (count($prices) == 0) {
            $prices = parent::getPrices();
        }
        return $prices;
    }

    /**
     * Simple Pricing is on, we need to take the dates from the same product we used to take the price
     *
     * @param array $params
     * @return string
     */
    protected function mapDirectiveSalePriceEffectiveDate($params = array())
    {
        $min_assoc = null;
        $min_price = PHP_INT_MAX;

        foreach ($this->getAssocMaps() as $assoc) {
            $prices = $assoc->getPrices();
            if ($prices['sp_excl_tax'] < $min_price) {
                $min_price = $prices['sp_excl_tax'];
                $min_assoc = $assoc;
            }
        }

        if (!is_null($min_assoc)) {
            $params['force_assoc'] = true;
            return $min_assoc->mapDirectiveSalePriceEffectiveDate($params);
        }

        return '';
    }

    /**
     * SCP configurable takes the promo price from the min price assoc.
     *
     * @param bool|true $process_rules
     * @param null $product
     * @return bool
     */
    public function hasSpecialPrice($process_rules = true, $product = null)
    {
        $has = false;
        $min_assoc = null;
        $min_price = PHP_INT_MAX;

        foreach ($this->getAssocMaps() as $assoc) {
            $prices = $assoc->getPrices();
            $price = min($prices['sp_excl_tax'], $prices['sp_excl_tax']);
            if ($price < $min_price) {
                $min_price = $price;
                $min_assoc = $assoc;
            }
        }

        if (!is_null($min_assoc)) {
            $has = $min_assoc->hasSpecialPrice($process_rules, $product);
        }

        return $has;
    }
}