<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category  RocketWeb
 * @package   RocketWeb_GoogleBaseFeedGenerator
 * @copyright Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */


/**
 * Config data model
 *
 * @category RocketWeb
 * @package  RocketWeb_GoogleBaseFeedGenerator
 *
 * @method $this setPath() setPath($string)
 * @method $this setValue() setValue($value)
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Config extends Mage_Core_Model_Abstract
{
    /*
     * We keep using feedconfigdata naming for time saving purposes and legacy overall.
     */
    protected function _construct() {
        $this->_init('googlebasefeedgenerator/config');
    }

    /**
     * Force data preparation.
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _afterLoad()
    {
        $this->addData($this->getData());
        parent::_afterLoad();
    }

    /**
     * Processing object after save data. Form data preparation after submitting.
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        switch($this->getPath()) {
            case 'columns_map_product_columns':
                $value = Mage::getModel('googlebasefeedgenerator/system_config_backend_serialized_mapproductcolumns')
                    ->filterValueBeforeSave($this->getValue());
                $this->setValue($value);
                break;
            case 'categories_provider_taxonomy_by_category':
                $value = Mage::getModel('googlebasefeedgenerator/system_config_backend_serialized_json')
                    ->filterValueBeforeSave($this->getValue());
                $this->setValue($value);
                break;
            case 'filters_adwords_price_buckets':
            case 'filters_map_replace_empty_columns':
            case 'filters_find_and_replace':
                $value = Mage::getModel('googlebasefeedgenerator/system_config_backend_serialized')
                    ->filterValueBeforeSave($this->getValue());
                $this->setValue($value);
                break;
            case 'filters_attribute_sets':
            case 'filters_product_types':
            case 'filters_skip_column_empty':
            case 'shipping_methods':
            case 'shipping_country':
            case 'shipping_country_with_region':
                $this->setValue(implode(',', $this->getValue()));
                break;
            case 'columns_stock_attribute_code':
                $value = Mage::getModel('googlebasefeedgenerator/system_config_backend_attributecode')
                    ->filterValueBeforeSave($this->getValue());
                $this->setValue($value);
                break;
        }
        parent::_beforeSave();
    }

    /**
     * Data backend processing before rendering
     *
     * @param array $arr
     * @return $this|Varien_Object
     */
    public function addData(array $arr)
    {
        parent::addData($arr);

        // don't process bad rows
        if (empty($arr) || !array_key_exists('path', $arr) || !array_key_exists('value', $arr)) {
            return $this;
        }

        switch($arr['path']) {
            case 'columns_map_product_columns':
                $value = Mage::getModel('googlebasefeedgenerator/system_config_backend_serialized_mapproductcolumns')
                    ->setData($this->getData())
                    ->filterValueAfterLoad($arr['value']);
                $this->setValue($value);
                break;
            case 'categories_provider_taxonomy_by_category':
                $value = Mage::getModel('googlebasefeedgenerator/system_config_backend_serialized_json')
                    ->setData($this->getData())
                    ->filterValueAfterLoad($arr['value']);
                // Fill in the levels for ordering category matching
                if (is_array($value)) {
                    foreach ($value as $key => $arr) {
                        if (!array_key_exists('order', $arr) && array_key_exists('category', $arr) && $arr['category'] > 0) {
                            $category = Mage::getModel('catalog/category')->load($arr['category']);
                            $value[$key]['order'] = -(int)$category->getLevel();
                        }
                    }
                } else {
                    $value = array();
                }
                $this->setValue($value);
                break;
            case 'filters_adwords_price_buckets':
            case 'filters_map_replace_empty_columns':
            case 'filters_find_and_replace':
                $value = Mage::getModel('googlebasefeedgenerator/system_config_backend_serialized')
                    ->setData($this->getData())
                    ->filterValueAfterLoad($arr['value']);
                $this->setValue($value);
                break;
            case 'filters_attribute_sets':
            case 'filters_product_types':
            case 'filters_skip_column_empty':
            case 'shipping_methods':
            case 'shipping_country':
            case 'shipping_country_with_region':
                if (!is_array($arr['value'])) {
                    $this->setValue(explode(',', $arr['value']));
                }
                break;
        }

        return $this;
    }
}
