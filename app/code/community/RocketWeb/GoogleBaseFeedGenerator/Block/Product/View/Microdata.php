<?php

/**
 * Class RocketWeb_GoogleBaseFeedGenerator_Block_Product_View_Microdata
 *
 * Microdata block - supports all default product types. Configurables will include information about children
 * Uses the Feed Generator to generate map for the current product
 *  - does not create/verify locks for the generator
 *  - the map will only include required columns, hard-coded in a property
 *
 * @usage $list = $block->setProduct($product)->getMicrodata();
 *        $microdata = $list[0];
 *        $microdata->getName();
 *        $microdata->getPrice();
 *        $microdata->getCurrency();
 *        $microdata->getAvailability();
 *
 * @see RocketWeb_GoogleBaseFeedGenerator_Model_Generator
 */
class RocketWeb_GoogleBaseFeedGenerator_Block_Product_View_Microdata
    extends Mage_Catalog_Block_Product_View_Abstract
{

    const XML_PATH_ENABLED              = 'rocketweb_googlebasefeedgenerator/general/microdata_turned_on';
    const XML_PATH_INCLUDE_TAX          = 'rocketweb_googlebasefeedgenerator/general/microdata_include_tax';
    const XML_PATH_CONDITION_ATTRIBUTE  = 'rocketweb_googlebasefeedgenerator/general/microdata_condition_attribute';

    /** @var array columns to generate by the map generator */
    protected $_columns = array('id', 'price', 'sale_price', 'availability', 'title', 'condition');

    /**
     * @return bool
     */
    public function isEnabled() {
        $store = Mage::app()->getStore();
        return (Mage::getStoreConfig(self::XML_PATH_ENABLED, $store) == "1");
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return Varien_Object[]
     */
    public function getMicrodata() {
        /** @var Varien_Object[] $microdata_list */
        $microdata_list = array();

        $product = $this->getProduct();

        if ($this->isEnabled() && $product && $product->getId()) {
            try {
                $row = $this->_getRow();
                $object = $this->_createRowObject($row);
                if ($object) {
                    $microdata_list[] = $object;
                }
            }
            catch (Exception $e) {
                Mage::logException($e);
            }
        }

        return $microdata_list;
    }

    /**
     * Converts map array to microdata Object
     *
     * @param array $map map array returned by the generator
     * @return null|Varien_Object
     */
    protected function _createRowObject($map) {
        if (empty($map['price']) || empty($map['availability']) || empty($map['title'])) {
            return null;
        }

        $microdata = new Varien_Object();
        $microdata->setName($map['title']);
        $microdata->setId($map['id']);
        if (!empty($map['sale_price'])) {
            $price = $map['sale_price'];
        }
        else {
            $price = $map['price'];
        }
        $microdata->setPrice(Zend_Locale_Format::toNumber($price, array(
            'precision' => 2,
            'number_format' => '#0.00'
        )));

        $microdata->setCurrency(Mage::app()->getStore()->getCurrentCurrencyCode());
        if ($map['availability'] == 'in stock') {
            $microdata->setAvailability('http://schema.org/InStock');
        }
        else {
            $microdata->setAvailability('http://schema.org/OutOfStock');
        }

        if (array_key_exists('condition', $map)) {
            if (strcasecmp('new', $map['condition']) == 0) {
                $microdata->setCondition('http://schema.org/NewCondition');
            }
            else if (strcasecmp('used', $map['condition']) == 0) {
                $microdata->setCondition('http://schema.org/UsedCondition');
            }
            else if (strcasecmp('refurbished', $map['condition']) == 0) {
                $microdata->setCondition('http://schema.org/RefurbishedCondition');
            }
        }

        return $microdata;
    }

    /**
     * Set a new product and reset maps to force re-generation
     *
     * @param Mage_Catalog_Model_Product $product
     * @return $this
     */
    public function setProduct(Mage_Catalog_Model_Product $product) {
        $this->setData('product', $product);
        return $this;
    }

    /**
     * Retrieves all maps for current product - will include children maps if any
     *
     * @return array
     */
    protected function _getRow() {

        $store = Mage::app()->getStore();
        $includeTax = (bool)Mage::getStoreConfig(self::XML_PATH_INCLUDE_TAX, $store);
        $conditionAttribute = Mage::getStoreConfig(self::XML_PATH_CONDITION_ATTRIBUTE, $store);

        $assocId = $this->getRequest()->getParam('aid', false);
        $mapClass = $this->getRequest()->getParam('m', 'abstract');
        $product = $assocId === false ? $this->getProduct() : Mage::getModel('catalog/product')->load($assocId);
        if ($assocId !== false) {
            $mapClass .= '_associated';
        }

        $feed = Mage::getModel('googlebasefeedgenerator/feed')->load($store->getId(), 'store_id');
        if (!$feed || !$feed->getId()) {
            // Load generic feed
            $feed = Mage::getModel('googlebasefeedgenerator/feed')->load(0);
        }

        $mapModel = false;
        $model_args = array('feed' => $feed, 'product' => $product);
        $file_path = Mage::getConfig()->getModuleDir('model', 'RocketWeb_GoogleBaseFeedGenerator')
            . DS. 'Model'. DS. 'Map'. DS. 'Product'. DS. str_replace("_", DS, $mapClass). '.php';
        if (file_exists($file_path)) {
            $mapModel = Mage::getModel('googlebasefeedgenerator/map_product_'. $mapClass, $model_args);
        }
        if (!$mapModel) {
            $mapModel = Mage::getModel('googlebasefeedgenerator/map_product_abstract', $model_args);
        }

        if ($assocId !== false) {
            $parentMap = new Varien_Object();
            $parentMap->setProduct($this->getProduct());
            $mapModel->setParentMap($parentMap);
        }

        $data = $this->_mapProduct($product, $mapModel, $includeTax, $conditionAttribute);
        return $data;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract $map
     */
    protected function _mapProduct($product, $map, $includeTax = null, $conditionAttribute = null)
    {
        // 'id', 'price', 'sale_price', 'availability', 'title', 'condition'
        if ($includeTax === null) {
            $_taxHelper = Mage::helper('tax');
            $includeTax = $_taxHelper->displayPriceIncludingTax();
        }
        $condition = 'new';
        if ($conditionAttribute !== null) {
            $value = $product->getData($conditionAttribute);
            if (!empty($value) && in_array($value, array('new', 'used', 'refurbished'))) {
                $condition = $value;
            }
        }

        $priceParams = array('map' => array('param' => $includeTax));

        $map = array(
            'id' => $map->mapDirectiveId(),
            'title' => $map->cleanField($product->getName()),
            'price' => $map->mapDirectivePrice($priceParams),
            'sale_price' => $map->mapDirectiveSalePrice($priceParams),
            'availability' => $map->mapDirectiveAvailability(),
            'condition' => $condition
        );
        return $map;
    }

}
