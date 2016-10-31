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
 */

/**
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @author     RocketWeb
 */

/**
 * @var $installer RocketWeb_GoogleBaseFeedGenerator_Model_Resource_Eav_Mysql4_Setup
 */
$installer = $this;
$installer->startSetup();

// Update old installs to use the direct attribute instead of the removed directive Manufacturer / Brand.
$attribute_code = $installer->getConnection()->fetchOne("SELECT value from `{$this->getTable('core_config_data')}` WHERE path = 'rocketweb_googlebasefeedgenerator/columns/manufacturer_attribute_code'");
if ($attribute_code) {
    $column_map = $installer->getConnection()->fetchOne("SELECT value from `{$this->getTable('core_config_data')}` WHERE path = 'rocketweb_googlebasefeedgenerator/columns/map_product_columns'");
    if ($column_map) {
        $change = false;
        $column_map = unserialize($column_map);
        foreach ($column_map as $k => $map) {
            if ($map['column'] == 'brand' && $map['attribute'] != $attribute_code) {
                $column_map[$k]['attribute'] = $attribute_code;
                $change = true;
                break;
            }
        }
        if ($change) {
            $installer->getConnection()->update($this->getTable('core_config_data'), array('value' => serialize($column_map)), "path = 'rocketweb_googlebasefeedgenerator/columns/map_product_columns'");
        }
    }
}

$installer->endSetup();