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
 * @copyright Copyright (c) 2016 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Helper_Image extends Mage_Catalog_Helper_Image
{
    protected $_store = null;

    public function setStore($store)
    {
        $this->_store = $store;
        return $this;
    }
    
    /**
     * Overwritten to force Feed's store and use own product image model.
     */
    public function init(Mage_Catalog_Model_Product $product, $attributeName, $imageFile=null)
    {
        $this->_reset();
        $this->_setModel(Mage::getModel('googlebasefeedgenerator/product_image', array('store' => $this->_store)));
        $this->_getModel()->setDestinationSubdir($attributeName);
        $this->setProduct($product);

        $this->setWatermark(
            Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_image", $this->_store)
        );
        $this->setWatermarkImageOpacity(
            Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_imageOpacity", $this->_store)
        );
        $this->setWatermarkPosition(
            Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_position", $this->_store)
        );
        $this->setWatermarkSize(
            Mage::getStoreConfig("design/watermark/{$this->_getModel()->getDestinationSubdir()}_size", $this->_store)
        );

        if ($imageFile) {
            $this->setImageFile($imageFile);
        } else {
            // add for work original size
            $this->_getModel()->setBaseFile($this->getProduct()->getData($this->_getModel()->getDestinationSubdir()));
        }
        return $this;
    }
}