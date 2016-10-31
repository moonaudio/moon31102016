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
 * @copyright Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Feedcolumns extends Varien_Object
{
    protected $_columns = array();

    protected function _getColumns()
    {
        if (empty($this->_columns)) {
            $config = Mage::registry('googlebasefeedgenerator_feed')->getConfig();
            $columns = $config['columns_map_product_columns'];

            if (is_array($columns)) {
                foreach ($columns as $arr) {
                    if (isset($arr['column']) && !isset($feed_columns[$arr['column']])) {
                        $this->_columns[$arr['column']] = $arr['column'];
                    }
                }
            }
            asort($this->_columns);
        }

        return $this->_columns;
    }

    public function toOptionArray()
    {
        $options = array(array('value' => '', 'label' => ''));
        foreach ($this->_getColumns() as $k => $v) {
            $options[] = array('value' => $k, 'label' => $v);
        }

        return $options;
    }

    public function toArray(array $arrAttributes = array())
    {
        $options = array(array('value' => '', 'label' => ''));
        foreach ($this->_getColumns() as $k => $v) {
            $options[$k] = $v;
        }

        return $options;
    }
}