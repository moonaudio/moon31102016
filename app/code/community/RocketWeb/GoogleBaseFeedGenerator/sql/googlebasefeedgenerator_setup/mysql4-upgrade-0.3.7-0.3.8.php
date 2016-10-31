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

// Install manufacturer attribute if missing
$id = $installer->getConnection()->fetchOne("SELECT `attribute_id` FROM `{$this->getTable('eav_attribute')}` WHERE `attribute_code` = 'manufacturer'");
if (!$id) {
    $installer->addAttribute(
        'catalog_product', 'manufacturer', array(
            'type' => 'varchar',
            'input' => 'text',
            'label' => 'Manufacturer',
            'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
            'note' => 'Manufacturer name of the product. By default mapped in google shopping feed to brand column',
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'visible_on_front' => false,
            'used_for_price_rules' => false,
            'position' => 80,
            'group' => 'Google Shopping Feed',
            'default' => '',
            'class' => '',
            'source' => '',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'unique' => false,
            'is_configurable' => false
        )
    );
}

$installer->endSetup();