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
class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Giftcard extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract
{
    /**
     * @return float|void
     */
    public function getPrices()
    {
        if ($this->hasData('price_array')) {
            return $this->getData('price_array');
        }

        /** @var Mage_Weee_Helper_Data $weeeHelper */
        $weeeHelper = $this->_getHelper('weee');
        /** @var RocketWeb_GoogleBaseFeedGenerator_Helper_Tax $taxHelper */
        $taxHelper = $this->_getHelper('googlebasefeedgenerator/tax');
        $helper = $this->_getHelper();
        $store = $this->getStore();
        $algorithm = $taxHelper->getConfig()->getAlgorithm($store);
        $isVersion1702OrLess = version_compare(Mage::getVersion(), '1.7.0.2', '<=');

        /** @var Mage_Catalog_Model_Product $product */
        $product = $this->getProduct();

        if ($helper->isModuleEnabled('Aitoc_Aitcbp')) {
            $product = $product->load($product->getid());
        }

        $qtyIncrements = $helper->getQuantityIcrements($product);


        $allowedAmounts = array();
        foreach ($product->getGiftcardAmounts() as $value) {
            $allowedAmounts[] = Mage::app()->getStore()->roundPrice($value['website_value']);
        }
        $price = min($allowedAmounts);

        if ($price <= 0) {
            if ($product->getAllowOpenAmount()) {
                $minPrice = $product->getOpenAmountMin();
                $price = ($minPrice > 0) ? $minPrice : 1;
            }
        }


        $prices = array();
        // Compute equivalent to default/template/catalog/product/price.phtml
        $convertedPrice = $this->convertPrice($price);
        $prices['p_excl_tax'] = $taxHelper->getPrice($product, $convertedPrice);
        $prices['p_incl_tax'] = $taxHelper->getPrice($product, $convertedPrice, true);

        $catalogRulesPrice = $this->getPriceByCatalogRules();
        $finalPrice = $catalogRulesPrice ? min($catalogRulesPrice, $product->getFinalPrice()) : $product->getFinalPrice();
        $convertedFinalPrice = $this->convertPrice($finalPrice);

        $prices['sp_excl_tax'] = $taxHelper->getPrice($product, $convertedFinalPrice);
        $prices['sp_incl_tax'] = $taxHelper->getPrice($product, $convertedFinalPrice, true);

        if ($algorithm !== Mage_Tax_Model_Calculation::CALC_UNIT_BASE && $qtyIncrements > 1.0) {
            // We need to multiply base before calculating tax for whole ((itemPrice * qty) + vat = total)
            $prices['p_excl_tax'] *= $qtyIncrements;
            $prices['p_incl_tax'] = $taxHelper->getPrice($product, $prices['p_excl_tax'], true);

            $prices['sp_excl_tax'] *= $qtyIncrements;
            $prices['sp_incl_tax'] = $taxHelper->getPrice($product, $prices['sp_excl_tax'], true);
        } else if ($qtyIncrements > 1.0) {
            // We just need to multiply incl_tax/excl_tax prices
            foreach ($prices as $code => $price) {
                $prices[$code] = $price * $qtyIncrements;
            }
        }

        foreach ($prices as $code => $price) {
            /**
             * Version <= 1.7.0.2 doesn't use roundPrice()
             * so we need to change it
             */
            if (!$isVersion1702OrLess) {
                $price = $store->roundPrice($price);
            }
            $prices[$code] = $price;
        }

        $this->setData('price_array', $prices);
        return $this->getData('price_array');
    }
}
