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
class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Shipping extends Varien_Object
{

    /**
     * @var Mage_Sales_Model_Quote_Address_Item
     */
    protected $_item;

    /**
     * @var array
     */
    protected $_rates = array();

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quoteModel;

    /**
     * @var Mage_Sales_Model_Quote_Address
     */
    protected $_addressModel;

    /**
     * @var Mage_Shipping_Model_Shipping
     */
    protected $_shippingModel;

    /**
     * @var RocketWeb_GoogleBaseFeedGenerator_Helper_Tax
     */
    protected $_taxHelper;

    protected $_shipping_territory = null;

    /**
     * @param array $products
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Shipping
     */
    public function setItem($product, $parentProduct = null)
    {
        $quote = Mage::getModel('sales/quote')->setStore(Mage::app()->getStore($this->getStoreId()));
        $this->_item = Mage::getModel('sales/quote_item');
        $this->_item->setQuote($quote);
        $this->_item->setProduct($product);
        $this->_item->setPrice($product->getPrice());
        $this->_item->setQty(1);
        $this->_item->setWeight($product->getWeight());

        if (!is_null($parentProduct)) {
            $parentItem = Mage::getModel('sales/quote_address_item');
            $parentItem->setProduct($parentProduct);
            $parentItem->setPrice($parentProduct->getPrice());
            $parentItem->setQty(1);
            $parentItem->setWeight($parentProduct->getWeight());
            $this->_item->setParentItem($parentItem);
        }

        return $this;
    }

    /**
     * @param $item
     * @param $limitCarrier
     * @param $address
     * @param null $country_id
     * @param null $region_id
     * @return false|Mage_Core_Model_Abstract
     */
    protected function setRequest($item, $limitCarrier, $address, $country_id = null, $region_id = null)
    {
        $request = Mage::getModel('shipping/rate_request');
        $item->setAddress($address);
        $item->setProductId($item->getProduct()->getId());

        $map_prices = $this->getMapProduct()->getPrices();
        $salePriceExclTax = $map_prices['sp_excl_tax'];
        $priceExclTax = $map_prices['p_excl_tax'];

        if (($salePriceExclTax > 0 && $salePriceExclTax < $priceExclTax)
            || ($salePriceExclTax > 0 && $priceExclTax <= 0)
        ) {
            $address->setBaseSubtotal($salePriceExclTax);
            $address->setBaseSubtotalInclTax($map_prices['sp_incl_tax']);
        } else {
            $address->setBaseSubtotal($priceExclTax);
            $address->setBaseSubtotalInclTax($map_prices['p_incl_tax']);
        }

        $item->setBaseSubtotal($priceExclTax);
        $item->setBaseSubtotalInclTax($map_prices['p_incl_tax']);

        $item->setBaseRowTotal($priceExclTax);
        $item->setBaseRowTotalInclTax($map_prices['p_incl_tax']);

        $item->setRowTotal($priceExclTax);
        $item->setRowTotalInclTax($map_prices['p_incl_tax']);

        if (!$salePriceExclTax) $salePriceExclTax = $priceExclTax;
        $discountAmount = $priceExclTax - $salePriceExclTax;
        if ($discountAmount >= 0) {
            $item->setDiscountAmount($discountAmount);
        }


        $ship_column = 'shipping_weight';
        $map = $this->getColumnsMap();
        $unit = $map[$ship_column]['param'];

        $weight = $this->getMapProduct()->mapColumn($ship_column);
        if ($unit != "") {
            $weight = trim(str_replace($unit, "", $weight));
        }
        $address->setWeight($weight);
        $address->setItemQty(1);
        if (!$item->getWeight()) {
            $item->setWeight($weight);
        }

        if (!is_null($country_id)) {
            $request->setDestCountryId($country_id);
            $address->setCountryId($country_id);
        }
        if (!is_null($region_id)) {
            $request->setDestRegionId($region_id);
            $address->setRegionId($region_id);
        }

        $request->setAllItems(array($item));
        //$request->setDestPostcode(?);
        $request->setPackageValue($address->getBaseSubtotal());
        //$request->setPackageValueWithDiscount($address->getBaseSubtotalWithDiscount());
        $request->setPackageWeight($address->getWeight());
        $request->setFreeMethodWeight($address->hasFreeMethodWeight() ? $address->getFreeMethodWeight() : $address->getWeight());
        $request->setPackageQty($address->getItemQty()); //$request->setPackageQty(1);
        $request->setStoreId($this->getStoreId());
        $request->setWebsiteId($this->getWebsiteId());
        $request->setBaseCurrency(Mage::app()->getStore($this->getStoreId())->getBaseCurrency());
        $request->setPackageCurrency(Mage::app()->getStore($this->getStoreId())->getCurrentCurrency());
        $request->setLimitCarrier($limitCarrier);
        $request->setBaseSubtotalInclTax($address->getBaseSubtotalInclTax());

        return $request;
    }

    /**
     * @return $this
     */
    public function collectRates()
    {
        $this->_rates = array();
        $allowed_carriers = $this->getAllowedCarriers();

        $ter = $this->getShippingTerritory();
        if (empty($ter)) {
            return $this;
        }

        $only_minimum = $this->getGenerator()->getFeed()->getConfig('shipping_only_minimum');
        $only_free_shipping = $this->getGenerator()->getFeed()->getConfig('shipping_only_free_shipping');

        if (!empty($allowed_carriers)) {
            foreach ($ter as $country_id => $regions) {
                if (is_array($regions) && count($regions) > 0) {
                    foreach ($regions as $region_id => $region_code) {

                        $quote = clone $this->_getQuoteModel();
                        $address = clone $this->_getAddressModel();
                        $address->setQuote($quote);

                        $this->_item->setAddress($address);
                        $this->_item->calcRowTotal();

                        $request = $this->setRequest($this->_item, $allowed_carriers, $address, $country_id, $region_id);
                        $shipping = clone $this->_getShippingModel();
                        $shipping->collectRates($request);
                        $result = $shipping->getResult();
                        if ($only_minimum) {
                            $result->sortRatesByPrice();
                        }
                        $result = $result->asArray();
                        if (empty($result)) {
                            continue;
                        }
                        if ($only_minimum && is_array($result)) {
                            reset($result);
                            $result = array(key($result) => current($result));
                        }
                        if ($only_free_shipping) {
                            $result = $this->filterFreeShipping($result);
                        }
                        $this->_rates[$country_id][$region_code] = $result;
                    }
                } else {
                    $quote = clone $this->_getQuoteModel();
                    $address = clone $this->_getAddressModel();
                    $address->setQuote($quote);

                    $this->_item->setAddress($address);
                    $this->_item->calcRowTotal();

                    $request = $this->setRequest($this->_item, $allowed_carriers, $address, $country_id, null);
                    $shipping = clone $this->_getShippingModel();
                    $shipping->collectRates($request);
                    $result = $shipping->getResult();
                    if ($only_minimum) {
                        $result->sortRatesByPrice();
                    }
                    $result = $result->asArray();
                    if (empty($result)) {
                        continue;
                    }
                    if ($only_minimum && is_array($result)) {
                        reset($result);
                        $result = array(key($result) => current($result));
                    }
                    if ($only_free_shipping) {
                        $result = $this->filterFreeShipping($result);
                    }

                    $this->_rates[$country_id]["*"] = $result;
                }
            }
        }

        return $this;
    }

    /**
     * @param $result
     * @return array
     */
    public function filterFreeShipping($result)
    {
        $f = false;
        if (is_array($result)) {
            foreach ($result as $carrier) {
                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                    foreach ($carrier['methods'] as $method_code => $method) {
                        if ($method['price'] <= 0) {
                            $f = true;
                            break;
                        }
                    }
                }
                if ($f) {
                    break;
                }
            }
        }

        $ret = $result;
        if ($f) {
            foreach ($result as $carrier_code => $carrier) {
                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                    foreach ($carrier['methods'] as $method_code => $method) {
                        if ($method['price'] > 0) {
                            unset($ret[$carrier_code]['methods'][$method_code]);
                        }
                    }
                }

                if (!(is_array($ret[$carrier_code]) && isset($ret[$carrier_code]['methods']) && is_array($ret[$carrier_code]['methods']) && count($ret[$carrier_code]['methods']) > 0)) {
                    unset($ret[$carrier_code]);
                }
            }
        }

        return $ret;
    }

    /**
     * @return array|string
     */
    public function getFormatedValue()
    {
        if (!(is_array($this->_rates) && count($this->_rates) > 0)) {
            return "";
        }

        /** @var RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract $mapProduct */
        $mapProduct = $this->getMapProduct();

        $this->_rates = $this->minimiseData($this->_rates);
        $v = array();
        foreach ($this->_rates as $country_id => $regions) {
            if (empty($regions)) {
                continue;
            }

            if (is_array($regions)) {
                foreach ($regions as $region_id => $carriers) {
                    if ($region_id == "*") {
                        if (is_array($carriers)) {
                            foreach ($carriers as $carrier) {
                                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                    foreach ($carrier['methods'] as $method) {
                                        $price = $mapProduct->convertPrice($this->getPriceTax($method['price'], $country_id)) . ' '. $this->getMapProduct()->getData('store_currency_code');
                                        $v[] = sprintf("%s::%s:%s", $country_id, $this->_getShippingTitle($carrier, $method), $price);
                                    }
                                }
                            }
                        }
                        break; // Only 1 set of carriers for countries without shipping vary by region.
                    } else {
                        if (is_array($carriers)) {
                            foreach ($carriers as $carrier) {
                                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                    foreach ($carrier['methods'] as $method_code => $method) {
                                        $quote = clone $this->_getQuoteModel();
                                        $address = clone $this->_getAddressModel();
                                        $address->setQuote($quote);
                                        $address->setCountryId($country_id);
                                        $price = $mapProduct->convertPrice($this->getPriceTax($method['price'], $country_id, $region_id)) . ' '. $this->getMapProduct()->getData('store_currency_code');
                                        $v[] = sprintf("%s:%s:%s:%s", $country_id, $region_id, $this->_getShippingTitle($carrier, $method), $price);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $v = implode(",", $v);
        return $v;
    }

    /**
     * Get title slot from shipping format ::{title}:
     *
     * @param  $carrier
     * @param  $method
     * @return string
     */
    protected function _getShippingTitle($carrier, $method)
    {
        $title = '';

        if ($method['title'] != "*" && (!empty($carrier['title']) || !empty($method['title']))) {
            if (!empty($carrier['title'])) {
                $title .= $carrier['title'];
            }
            if (!empty($method['title'])) {
                $title .= empty($title) ? $method['title'] : ' - ' . $method['title'];
            }
        }
        return $title;
    }

    /**
     * @param $price
     * @param null $country_id
     * @param null $region_id
     * @return float
     */
    protected function getPriceTax($price, $country_id = null, $region_id = null)
    {
        $ret = $price;

        // Apply if there is a tax for shipping
        if ($this->_getTaxHelper()->getShippingTaxClass($this->getStoreId())) {
            $includingTax = ($this->getGenerator()->getFeed()->getConfig('shipping_add_tax_to_price') ? true : false);
            $quote = clone $this->_getQuoteModel();
            $address = clone $this->_getAddressModel();
            $address->setQuote($quote);
            if (!is_null($country_id)) {
                $address->setCountryId($country_id);
                if (!is_null($region_id) && $region_id != "*") {
                    $address->setRegionId($region_id);
                }
            }

            $billingAddress = clone $address;
            $billingAddress->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING);
            $shippingAddress = clone $address;
            $shippingAddress->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
                ->setSameAsBilling(1);
            $quote->setBillingAddress($billingAddress)
                ->setShippingAddress($shippingAddress);

            $ret = $this->_getTaxHelper()->getShippingPrice($price, $includingTax, $shippingAddress, null, $this->getStoreId());
        }

        return $ret;
    }

    /**
     * Transform rates from:
     *    US:CA:x:99 and US:NY:x:99 to US:*:x:99
     *
     * @todo:  US:z:X:99 and US:z:Y:99 to US:Z:*:99
     * @param  array $rates
     * @return array
     */
    public function minimiseData($rates)
    {
        $ret = $rates;
        if (empty($ret)) {
            return $ret;
        }

        /* Compress by regions
           US:CA:x:99 and US:NY:x:99 to US::x:99 */
        foreach ($rates as $country_id => $regions) {
            if (empty($regions)) {
                continue;
            }

            // Find all methods in all regions
            $all_methods = array();
            foreach ($regions as $region_id => $carriers) {
                if ($region_id != "*") {
                    if (is_array($carriers)) {
                        foreach ($carriers as $carrier_code => $carrier) {
                            if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                foreach ($carrier['methods'] as $method_code => $method) {
                                    $code = $carrier_code . '~' . $method_code;
                                    if (!isset($all_methods[$code])) {
                                        $all_methods[$code] = $code;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Find which methods have same prices in all regions ($same) and transform $ret: for each method in all regions with same price => set method in region_id == *.
            $same = array();
            foreach ($all_methods as $code => $v) {
                $same[$code] = PHP_INT_MAX;
            }

            foreach ($regions as $region_id => $carriers) {
                if ($region_id != "*") {
                    $all_region_methods = array();
                    if (is_array($carriers)) {
                        foreach ($carriers as $carrier_code => $carrier) {
                            if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                foreach ($carrier['methods'] as $method_code => $method) {
                                    $code = $carrier_code . '~' . $method_code;
                                    $all_region_methods[$code] = $code;
                                    if ($same[$code] == PHP_INT_MAX) {
                                        $same[$code] = $method['price'];
                                    } elseif ($same[$code] !== false && $same[$code] != $method['price']) {
                                        $same[$code] = false;
                                    }
                                }
                            }
                        }
                    }

                    $missing = array_diff($all_methods, $all_region_methods);
                    foreach ($missing as $code => $v) {
                        $same[$code] = false;
                    }
                }
            }

            foreach ($same as $code => $v) {
                if ($same[$code] === false) {
                    unset($same[$code]);
                }
            }

            // Move every redundant method to *
            if (count($same) > 0) {
                foreach ($regions as $region_id => $carriers) {
                    if ($region_id != "*") {
                        $all_region_methods = array();
                        if (is_array($carriers)) {
                            foreach ($carriers as $carrier_code => $carrier) {
                                if (is_array($carrier) && isset($carrier['methods']) && is_array($carrier['methods'])) {
                                    foreach ($carrier['methods'] as $method_code => $method) {
                                        $code = $carrier_code . '~' . $method_code;
                                        if (isset($same[$code])) {
                                            unset($ret[$country_id][$region_id][$carrier_code]['methods'][$method_code]);

                                            // move once to *
                                            if (!isset($ret[$country_id]["*"])) {
                                                $ret[$country_id]["*"] = array();
                                            }
                                            if (!isset($ret[$country_id]["*"][$carrier_code])) {
                                                $ret[$country_id]["*"][$carrier_code] = array(
                                                    'title' => $carrier['title'],
                                                    'methods' => array()
                                                );
                                            }
                                            if (!isset($ret[$country_id]["*"][$carrier_code]['methods'][$method_code])) {
                                                $ret[$country_id]["*"][$carrier_code]['methods'][$method_code] = $method;
                                            }
                                        }
                                    }

                                    // clean
                                    if (empty($ret[$country_id][$region_id][$carrier_code]['methods'])) {
                                        unset($ret[$country_id][$region_id][$carrier_code]);
                                    }
                                }
                            }
                        }
                    }

                    // clean
                    if (empty($ret[$country_id][$region_id])) {
                        unset($ret[$country_id][$region_id]);
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function getAllowedCarriers()
    {
        $carrier_realtime = explode(',', $this->getGenerator()->getFeed()->getConfig('shipping_carrier_realtime'));
        $methods = $this->getGenerator()->getFeed()->getConfig('shipping_methods');
        $allowed_carriers = array();
        foreach ($methods as $m) {
            if (!empty($m)) {
                $allowed_carriers[] = substr($m, 0, strpos($m, "_"));
            }
        }
        $allowed_carriers = array_unique($allowed_carriers);
        $allowed_carriers = array_diff($allowed_carriers, $carrier_realtime);

        return $allowed_carriers;
    }

    /**
     * @return array|null
     */
    public function getShippingTerritory()
    {
        if (!is_null($this->_shipping_territory)) {
            return $this->_shipping_territory;
        }

        $this->_shipping_territory = array();
        $allowed_countries = $this->getGenerator()->getFeed()->getConfig('shipping_country');
        if (empty($allowed_countries)) {
            return $this->_shipping_territory;
        }
        $by_region = $this->getGenerator()->getFeed()->getConfig('shipping_by_region');
        $allowed_country_with_region = array();
        if ($by_region) {
            $allowed_country_with_region = $this->getGenerator()->getFeed()->getConfig('shipping_country_with_region');
            $allowed_country_with_region = empty($allowed_country_with_region) ? $allowed_countries : array_intersect($allowed_country_with_region, $allowed_countries);

        }

        foreach ($allowed_countries as $country_id) {
            $this->_shipping_territory[$country_id] = array();
        }

        if (count($allowed_country_with_region) > 0) {
            $regionsCollection = Mage::getResourceModel('directory/region_collection')
                ->addCountryFilter($allowed_country_with_region)
                ->load();
            $countryRegions = array();
            foreach ($regionsCollection as $region) {
                $countryRegions[$region->getCountryId()][$region->getId()] = $region->getCode();
            }
            unset($regionsCollection);

            foreach ($allowed_country_with_region as $country_id) {
                $this->_shipping_territory[$country_id] = array();
                if ($by_region && isset($countryRegions[$country_id]) && count($countryRegions[$country_id] > 0)) {
                    $this->_shipping_territory[$country_id] = $countryRegions[$country_id];
                }
            }
        }

        return $this->_shipping_territory;
    }

    /**
     * @return false|Mage_Core_Model_Abstract|Mage_Sales_Model_Quote
     */
    protected function _getQuoteModel()
    {
        if (is_null($this->_quoteModel)) {
            $this->_quoteModel = Mage::getModel('sales/quote');
        }
        return $this->_quoteModel;
    }

    /**
     * @return false|Mage_Core_Model_Abstract|Mage_Sales_Model_Quote_Address
     */
    protected function _getAddressModel()
    {
        if (is_null($this->_addressModel)) {
            $this->_addressModel = Mage::getModel('sales/quote_address');
        }
        return $this->_addressModel;
    }

    /**
     * @return false|Mage_Core_Model_Abstract|Mage_Shipping_Model_Shipping
     */
    protected function _getShippingModel()
    {
        if (is_null($this->_shippingModel)) {
            $this->_shippingModel = Mage::getModel('shipping/shipping');
        }
        return $this->_shippingModel;
    }

    /**
     * @return Mage_Core_Helper_Abstract|RocketWeb_GoogleBaseFeedGenerator_Helper_Tax
     */
    protected function _getTaxHelper()
    {
        if (is_null($this->_taxHelper)) {
            $this->_taxHelper = Mage::helper('googlebasefeedgenerator/tax');
        }
        return $this->_taxHelper;
    }
}
