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
class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable_Associated extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract_Associated
{
    /**
     * When computing the special price, we send the $price parameter from associated items
     * The
     * @return mixed
     */
    public function getPriceByCatalogRules($price = null)
    {
        if (is_null($price)) {
            $price = $this->getProduct()->getPrice();
        }
        $product = $this->getParentMap()->getProduct();

        $price = Mage_Catalog_Model_Product_Type_Price::calculatePrice(
            $price,
            false, false, false, false,
            $this->getWebsiteId(),
            Mage::getStoreConfig('customer/create_account/default_group', $this->getFeed()->getStoreId()),
            $product->getId()
        );

        return $price;
    }

    /**
     * Computes prices for given or current product.
     * It returns an array of 4 prices: price and special_price, buth including and excluding tax
     *
     * @return mixed
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
        $store = Mage::app()->getStore($this->getStoreId());
        $algorithm = $taxHelper->getConfig()->getAlgorithm($store);
        $isVersion1702OrLess = version_compare(Mage::getVersion(), '1.7.0.2', '<=');

        /** @var Mage_Catalog_Model_Product $product */
        $product = $this->getParentMap()->getProduct();


        if ($helper->isModuleEnabled('Aitoc_Aitcbp')) {
            $product = $product->load($product->getid());
        }

        $qtyIncrements = $helper->getQuantityIcrements($product);

        $has_option_price = false;
        $price = $base_price = $product->getPrice();
        $rules_price = $this->getPriceByCatalogRules($product->getFinalPrice());
        $final_base_price = $final_price = $rules_price ? min($rules_price, $product->getFinalPrice()) : $product->getFinalPrice();

        // Calculate option prices
        $op_excl_tax = 0; $sop_excl_tax = 0;
        $configurable_attributes = $this->getTools()->getConfigurableAttributesAsArray($product);

        if (is_array($configurable_attributes)) {
            foreach ($configurable_attributes as $res) {
                if (is_array($res['values']) && count($res['values'])) {
                    $has_option_price = true;
                    foreach ($res['values'] as $value) {
                        if (isset($value['value_index']) && $this->getProduct()->getData($res['attribute_code']) == $value['value_index']) {
                            $option_price = floatval($value['pricing_value']);

                            // calculate final price according to Magento catalog rule event
                            // @see Mage_Catalog_Block_Product_View_Type_Configurable
                            $product->setConfigurablePrice($option_price, $value['is_percent']);
                            $product->setParentId(true);
                            Mage::dispatchEvent('catalog_product_type_configurable_price', array('product' => $product));
                            $option_sale_price = $product->getConfigurablePrice();

                            if (isset($value['is_percent']) && $value['is_percent']) {
                                $option_price = $base_price * $option_price / 100;
                                $option_sale_price = $final_base_price * $option_sale_price / 100;
                            }

                            // round converted option price before adding to full price,
                            // because that's the way Mage sends them in the JS
                            // @see the optionsPrice variable on the PDP
                            $option_price = $this->convertPrice($option_price);
                            $option_sale_price = $this->convertPrice($option_sale_price);
                            /**
                             * Version <= 1.7.0.2 doesn't use roundPrice()
                             * so we need it only for newer versions
                             */
                            if (!$isVersion1702OrLess) {
                                $option_price = $store->roundPrice($option_price);
                                $option_sale_price = $store->roundPrice($option_sale_price);
                            }
                            $op_excl_tax += $taxHelper->getPrice($product, $option_price);
                            $sop_excl_tax += $taxHelper->getPrice($product, $option_sale_price);
                            break;
                        }
                    }
                }
            }
        }
        $this->getProduct()->setData('option_price', $has_option_price);

        // Compute Weee tax
        $weeeExcludingTax = $weeeHelper->getAmountForDisplay($product);
        $weeeIncludingTax = $weeeExcludingTax;
        if ($weeeHelper->isTaxable()) {
            $weeeIncludingTax = $weeeHelper->getAmountInclTaxes($weeeHelper->getProductWeeeAttributesForRenderer($product, null, null, null, true));
        }

        // Calculate equivalent to js/varien/configurable.js
        // this gets option prices fully calculated, adds them to product price, then rounds

        $prices = array();
        // Compute equivalent to default/template/catalog/product/price.phtml
        $convertedPrice = $this->convertPrice($price);
        $prices['p_excl_tax'] = $taxHelper->getPrice($product, $convertedPrice, false, null, null, null, null, null, false) + $op_excl_tax;
        $prices['p_incl_tax'] = $taxHelper->getPrice($product, $convertedPrice + $op_excl_tax, true, null, null, null, null, null, false);

        $catalogRulesPrice = $this->getPriceByCatalogRules();
        $finalPrice = $catalogRulesPrice ? min($catalogRulesPrice, $product->getFinalPrice()) : $product->getFinalPrice();
        $convertedFinalPrice = $this->convertPrice($finalPrice);

        // tax percent * price + price = price incl tax
        // @see js/varien/configurable.js:681-683
        $prices['sp_excl_tax'] = $taxHelper->getPrice($product, $convertedFinalPrice + $sop_excl_tax);
        $prices['sp_incl_tax'] = $taxHelper->getPrice($product, $convertedFinalPrice + $sop_excl_tax, true);

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
            if (strpos($code, '_incl_') !== false) {
                $price = $price + $weeeIncludingTax;
            } else {
                $price = $price + $weeeExcludingTax;
            }
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

    /**
     * Note: Magento takes the sale price from parent if it's a configurable.
     *
     * @param  array $params
     * @return string
     */
    protected function mapDirectiveSalePriceEffectiveDate($params = array())
    {
        return $this->getParentMap()->mapDirectiveSalePriceEffectiveDate($params);
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
        $product = $this->getParentMap()->getProduct();


        // equivalent to default/template/catalog/product/msrp_price.phtml
        if ($this->_getHelper()->hasMsrp($product)){
            $qtyIncrements = $this->_getHelper()->getQuantityIcrements($product);
            $price = $this->convertPrice($product->getMsrp() * $qtyIncrements);
        }

        return ($price > 0) ? sprintf("%.2F", $price). ' '. $this->getData('store_currency_code') : '';
    }

    /**
     * Computes on the parent product
     *
     * @param bool|true $process_rules
     * @param null $product
     * @return bool
     */
    public function hasSpecialPrice($process_rules = true, $product = null)
    {
        if (is_null($product)) {
            $product = $this->getParentMap()->getProduct();
        }
        return parent::hasSpecialPrice($process_rules, $product);
    }
}
