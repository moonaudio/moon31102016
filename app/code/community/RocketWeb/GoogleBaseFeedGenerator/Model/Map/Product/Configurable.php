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
class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract
{
    /**
     * Support for configurable items product option not yet implemented
     * @return bool
     */
    protected function _isAllowProductOptions()
    {
        return false;
    }

    public function getChildrenCount() {
        return (count($this->getAssocIds()));
    }

    /**
     * Iterate through associated products and set mapping objects
     *
     * @return $this
     */
    public function _beforeMap()
    {
        if (!empty($this->_assocs) || $this->isSkip()) {
            return $this;
        }

        $this->_assocs = array();
        $assocs_no_skip = array();
        $stockStatusFlag = false;
        $stockStatus = false;
        $message = $this->getGenerator()->checkPriceRangeSkip($this->getProduct());
        if ($message !== false) {
            $this->setSkip($message);
        }
        foreach ($this->getAssocIds() as $assocId) {

            $is_skip = false;
            $assoc = Mage::getModel('catalog/product');
            $assoc->setStoreId($this->getFeed()->getStoreId());
            $assoc->getResource()->load($assoc, $assocId);
            $assoc->setData('quantity', 0);

            if ($this->getGenerator()->getData('verbose')) {
                echo $this->getGenerator()->formatMemory(memory_get_usage(true)) . " - Configurable associated SKU " . $assoc->getSku() . ", ID " . $assoc->getId() . "\n";
            }

            $stock = Mage::helper('googlebasefeedgenerator/maps')->getOutOfStockStatus();

            if (!$this->getFeed()->getConfig('general_use_default_stock')) {
                $stock_attribute = $this->getGenerator()->getAttribute($this->getFeed()->getConfig('general_stock_attribute_code'));
                if ($stock_attribute === false) {
                    Mage::throwException(sprintf('Invalid attribute for Availability column. Please make sure proper attribute is set under the setting "Alternate Stock/Availability Attribute.". Provided attribute code \'%s\' could not be found.', $this->getFeed()->getConfig('general_stock_attribute_code')));
                }

                $stock = trim(strtolower($this->getAttributeValue($assoc, $stock_attribute)));
                if (array_search($stock, Mage::helper('googlebasefeedgenerator/maps')->getAllowedStockStatuses()) === false) {
                    $stock = Mage::helper('googlebasefeedgenerator/maps')->getOutOfStockStatus();
                }
            } else {
                $stockItem = Mage::getModel('cataloginventory/stock_item');
                $stockItem->setStoreId($this->getFeed()->getStoreId());
                $stockItem->getResource()->loadByProductId($stockItem, $assoc->getId());
                $stockItem->setOrigData();

                if ($stockItem->getId() && $stockItem->getIsInStock()) {
                    $assoc->setData('quantity', $stockItem->getQty());
                    $stock = Mage::helper('googlebasefeedgenerator/maps')->getInStockStatus();
                }

                // Clear stockItem memory
                unset($stockItem->_data);
                $this->getTools()->clearNestedObject($stockItem);
            }

            // Skip assoc considering the appropriate stock status
            if (!$this->getFeed()->getConfig('configurable_add_out_of_stock')
                && $stock != Mage::helper('googlebasefeedgenerator/maps')->getInStockStatus())
            {
                $is_skip = true;
                $this->log(sprintf("product id %d sku %s, configurable item, skipped - out of stock", $assocId, $assoc->getSku()));
            }
            $message = $this->getGenerator()->checkPriceRangeSkip($assoc, ', configurable item');
            if ($message !== false) {
                $is_skip = true;
                $this->log($message);
            }
            $assocs_no_skip[] = $assoc;
            if (!$is_skip) {
                $this->_assocs[$assocId] = $assoc;
            }

            // Set stock status of the current item and check if the status has changed
            if ($stockStatus != false && $stock != $stockStatus) {
                $stockStatusFlag = true;
            }
            $stockStatus = $stock;
        }

        $this->setVariants($assocs_no_skip);

        // Set configurable stock status if all assocs have the same stock status, only for default stocks
        if ($this->getFeed()->getConfig('general_use_default_stock') && $stockStatus && !$stockStatusFlag) {
            $this->setAssociatedStockStatus($stockStatus);
            if ($stockStatus == Mage::helper('googlebasefeedgenerator/maps')->getOutOfStockStatus() && !$this->getFeed()->getConfig('filters_add_out_of_stock')) {
                $this->setSkip(sprintf("product id %d sku %s, configurable, skipped - out of stock.", $this->getProduct()->getId(), $this->getProduct()->getSku()));
            }
        }

        return parent::_beforeMap();
    }

    public function map()
    {
        $rows = array();
        $parentRow = null;
        $this->_beforeMap();

        if ($this->getTools()->isAllowConfigurableMode()) {
            if (!$this->isSkip()) {

                // simulate parent::map() without clearing associated_maps from memory, as associated more could be on.
                $row = parent::_map();
                reset($row); $parentRow = current($row);
                $this->_checkEmptyColumns($parentRow);

                // remove parent and skipping flag so that the associated items could still be processed.
                if ($this->isSkip()) {
                    $parentRow = null;
                }
            }
        }

        if ($this->getTools()->isAllowConfigurableAssociatedMode() && !$this->hasSkipAssocs() && $this->hasAssocMaps()) {

            foreach ($this->getAssocMaps() as $assocMap) {
                $row = $assocMap->map();
                reset($row); $row = current($row);
                if (!$assocMap->isSkip()) {
                    $rows[] = $row;
                }
            }
        }

        // Fill in parent columns specified in $inherit_columns with values list from associated items
        if (!is_null($parentRow)) {
            $this->mergeVariantValuesToParent($parentRow, $rows);
            array_unshift($rows, $parentRow);
        }

        // if any of the associated not skipped, force add them to the feed
        if (count($rows)) {
            $this->unSkip();
        }

        return $this->_afterMap($rows);
    }

    /**
     * @param $rows
     * @return array
     */
    public function _afterMap($rows)
    {
        // Free some memory
        if (is_array($this->_assocs)) {
            foreach ($this->_assocs as $assoc) {
                if ($assoc->getEntityid()) {
                    $this->getTools()->clearNestedObject($assoc);
                }
            }
        }

        $this->_cache_map_values = array();
        return $rows;
    }

    /**
     * Array with associated products ids in current store.
     *
     * @return array
     */
    public function getAssocIds()
    {
        if (is_null($this->_assoc_ids)) {
            $this->_assoc_ids = $this->loadAssocIds($this->getProduct(), $this->getFeed()->getStoreId());
        }
        return $this->_assoc_ids;
    }

    /**
     * Get value from lowest priced associated item when missing
     *
     * @param array $params
     * @return string
     */
    protected function mapDirectiveShippingWeight($params = array())
    {
        $weight = parent::mapDirectiveShippingWeight($params);
        if (empty($weight)) {
            $map = Mage::helper('googlebasefeedgenerator')->sortAssocsByPrice($this->getAssocMaps(), 'min');
            if (!is_null($map)) {
                $weight = $map->mapDirectiveShippingWeight($params);
            }
        }

        return $weight;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectiveQuantity($params = array())
    {
        $cell = $this->getInventoryCount();

        // If Qty not set at parent item, summarize it from associated items
        if ($params['map']['param'] == RocketWeb_GoogleBaseFeedGenerator_Model_Source_Directive_Product_Quantity::ITEM_SUM_DEFAULT_QTY) {
            $qty = 0;
            foreach ($this->_assocs as $assoc) {
                $qty += $assoc->getData('quantity');
            }
            $cell = $qty ? $qty : $cell;
        }

        $cell = sprintf('%d', $cell);
        $this->_findAndReplace($cell, $params['map']['column']);
        return $cell;
    }

    /**
     * @param array $params
     * @return string
     */
    public function mapDirectiveAvailability($params = array())
    {
        // Set the computed configurable stock status
        if ($this->hasAssociatedStockStatus() && $this->getAssociatedStockStatus() == Mage::helper('googlebasefeedgenerator/maps')->getOutOfStockStatus()) {
            return $this->cleanField($this->getAssociatedStockStatus(), $params);
        }

        return parent::mapDirectiveAvailability($params);
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

        /**
         * Special case when Configurable product price = 0
         * Usually this means there is extension using SCP price
         */
        if (!$price || $price == 0) {
            $price = $this->_getMinAssociatedPrice($includingTax, false);
        }

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
     * @param  array $params
     * @return string
     */
    public function mapDirectiveSalePrice($params = array())
    {
        if (!$this->hasSpecialPrice()) {
            return '';
        }
        $prices = $this->getPrices();
        $includingTax = array_key_exists('param', $params['map']) ? (boolean)$params['map']['param'] : true;
        $price = $includingTax ? $prices['sp_incl_tax'] : $prices['sp_excl_tax'];
        if (!$price || $price == 0) {
            $price = $this->_getMinAssociatedPrice($includingTax, true);
        }

        return ($price > 0) ? sprintf("%.2F", $price). ' '. $this->getData('store_currency_code') : '';
    }

    /**
     * Get price from associated products
     * This gets used when store is using SCP extensions
     * to set up the price only for associated simple products
     * and the Configurable product (special_)price = 0
     *
     * @param bool|true $tax
     * @param bool|false $sale
     * @return int|string
     */
    protected function _getMinAssociatedPrice($tax = true, $sale = false)
    {
        $assocMaps = $this->getAssocMaps();
        if (!empty($assocMaps)) {
            $min_price = PHP_INT_MAX;

            foreach ($assocMaps as $map) {
                $map_prices = $map->getPrices();
                $assoc_price =
                    $tax ? ($sale ? $map_prices['sp_incl_tax'] : $map_prices['p_incl_tax'])
                         : ($sale ? $map_prices['sp_excl_tax'] : $map_prices['p_excl_tax']);
                if ($min_price > $assoc_price && $assoc_price != 0) {
                    $min_price = $assoc_price;
                }
            }
            if ($min_price != PHP_INT_MAX) {
                return $min_price;
            }
        }
        return '';
    }

    /**
     * Get the identifier value from the less expensive associated item
     *
     * @param array $params
     * @return string
     */
    protected function mapDirectiveIdentifier($params = array())
    {
        $val = parent::mapDirectiveIdentifier($params);
        if (empty($val)) {
            $map = Mage::helper('googlebasefeedgenerator')->sortAssocsByPrice($this->getAssocMaps(), 'min');
            if (!is_null($map)) {
                $val = $map->mapDirectiveIdentifier($params);
            }
        }

        return $val;
    }

}
