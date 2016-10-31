<?php

$installer = $this;
/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */

$installer->startSetup();
$installer->addAttribute(
        'catalog_product', 'custom_countries', array(
    'group' => 'General',
    'type' => 'varchar',
    'backend' => 'eav/entity_attribute_backend_array',
    'user_defined' => '1',
    'frontend' => '',
    'label' => 'Shipping Available Countries',
    'input' => 'multiselect',
    'source' => 'Mage_Catalog_Model_Product_Attribute_Source_Countryofmanufacture',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE
        )
);

$installer->updateAttribute('catalog_product', 'custom_countries', 'eav/entity_attribute_backend_array', '');
$installer->endSetup();
?>