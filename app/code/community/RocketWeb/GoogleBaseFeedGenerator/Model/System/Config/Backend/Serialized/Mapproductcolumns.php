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

class RocketWeb_GoogleBaseFeedGenerator_Model_System_Config_Backend_Serialized_Mapproductcolumns extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    /**
     * Clean up empty rows and format column names with parameters
     */
    protected function _beforeSave()
    {
        $value = $this->_rwCleanValue($this->getValue());
        if (is_array($value)) {
            unset($value['__empty']);
            foreach ($value as $k => $v) {
                if (!is_array($v) || !array_key_exists('column', $v)) {
                    unset($value[$k]);
                }
            }
        }
        $this->setValue($value);

        return parent::_beforeSave();
    }

    /**
     * If the value was not set, it means it's a new feed and we'll fill in default column map
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        if ($this->getValue() === false && $this->hasData('feed')) {
            $ret = array();
            foreach ($this->getFeed()->getData('default_map_product_columns') as $directive => $columns) {
                foreach ($columns as $column) {
                    $ret[] = array(
                        'column' => $column['column'],
                        'attribute' => $directive,
                        'param' => (isset($column['param']) ? $column['param'] : ''),
                        'order' => (isset($column['order']) ? $column['order'] : ''),
                    );
                }
            }

            // sort the columns by 'order' key
            usort($ret, array($this, '_sortOrder'));

            $this->setValue($ret);
        }

        return $this;
    }

    protected function _sortOrder($a, $b) {
        return $a['order'] - $b['order'];
    }

    /**
     * Cleanup rows
     *
     * @param $value
     * @return array
     */
    protected function _rwCleanValue($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (is_array($v) && isset($v['column'])) {
                    $value[$k]['column'] = str_replace(" ", "_", trim(strtolower($v['column'])));
                    $value[$k]['column'] = preg_replace('/__*/', '_', $value[$k]['column']);
                    $value[$k]['column'] = trim($value[$k]['column'], "_");
                } elseif (is_array($v) && isset($v['param'])) {
                    $value[$k]['param'] = trim($v['param']);
                }
            }
        }
        return $value;
    }

    /**
     * Callback after load Config
     *
     * @param $value
     * @return string
     */
    public function filterValueAfterLoad($value)
    {
        $this->setValue($value);
        $this->_afterLoad();
        return $this->getValue();
    }

    /**
     * Callback before save Config
     *
     * @param $value
     * @return string
     */
    public function filterValueBeforeSave($value)
    {
        $this->setValue($value);
        $this->_beforeSave();
        return $this->getValue();
    }

}