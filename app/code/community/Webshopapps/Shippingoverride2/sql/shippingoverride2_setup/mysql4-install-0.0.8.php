<?php

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('shippingoverride2')};
CREATE TABLE {$this->getTable('shippingoverride2')} (
  pk int(10) unsigned NOT NULL auto_increment,
  website_id int(11) NOT NULL default '0',
  dest_country_id varchar(4) NOT NULL default '0',
  dest_region_id int(10) NOT NULL default '0',
  dest_city varchar(10) NOT NULL default '',
  dest_zip varchar(10) NOT NULL default '',
  dest_zip_to varchar(10) NOT NULL default '',
  special_shipping_group varchar(30) NOT NULL default '',
  weight_from_value decimal(12,4) NOT NULL default '-1.0000',
  weight_to_value decimal(12,4) NOT NULL default '0.0000',
  item_weight_from_value decimal(12,4) NULL default '-1.0000',
  item_weight_to_value decimal(12,4) NULL default '1000000',
  price_from_value decimal(12,4) NOT NULL default '0.0000',
  price_to_value decimal(12,4) NOT NULL default '0.0000',
  item_from_value decimal(12,4) NOT NULL default '0.0000',
  item_to_value decimal(12,4) NOT NULL default '0.0000',
  price decimal(12,4) NOT NULL default '0.0000',
  percentage varchar(255) NOT NULL default '0.0000',
  customer_group varchar(30) NOT NULL default '',
  cost decimal(12,4) NOT NULL default '0.0000',
  delivery_type varchar(255) NOT NULL default '',
  algorithm varchar(30) NOT NULL default '',
  rules varchar(255) NULL,
  PRIMARY KEY(`pk`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");
if  (Mage::helper('wsacommon')->getVersion() == 1.6) {

$installer->run("
select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
	attribute_code 	= 'special_shipping_group',
	backend_type	= 'int',
	frontend_input	= 'select',
	is_required	= 0,
	is_user_defined	= 1,
	used_in_product_listing	= 0,
	is_filterable_in_search	= 0,
	frontend_label	= 'Special Shipping Group';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='special_shipping_group';


insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'shipping_price',
    	backend_type	= 'decimal',
    	frontend_input	= 'price',
    	is_required	= 0,
    	is_user_defined	= 1,
    	used_in_product_listing = 0,
    	is_filterable_in_search	= 0,
    	frontend_label	= 'Shipping Price';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipping_price';

ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')}  ADD override_price_info TEXT;
	");

}else {

$installer->run("

select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
	attribute_code 	= 'special_shipping_group',
	backend_type	= 'int',
	frontend_input	= 'select',
	is_required	= 0,
	is_user_defined	= 1,
	frontend_label	= 'Special Shipping Group';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='special_shipping_group';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id = @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'shipping_price',
    	backend_type	= 'decimal',
    	frontend_input	= 'price',
    	is_required	= 0,
    	is_user_defined	= 1,
    	frontend_label	= 'Shipping Price';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='shipping_price';

insert ignore into {$this->getTable('catalog_eav_attribute')}
    set attribute_id = @attribute_id,
    	is_visible 	= 1,
    	used_in_product_listing	= 0,
    	is_filterable_in_search	= 0;

ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')}  ADD override_price_info varchar(255);

");

    if  (Mage::helper('wsacommon')->getNewVersion() != 1.6) {

        $overrideInfo =  array(
            'type'    	=> Varien_Db_Ddl_Table::TYPE_VARCHAR,
            'comment' 	=> 'Override Pricing Info',
            'nullable' 	=> 'true',
        );
    }
    else{
        $overrideInfo =  array(
            'type'    	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'comment' 	=> 'Override Pricing Info',
            'nullable' 	=> 'true',
        );
    }

$installer->getConnection()->addColumn($installer->getTable('sales/quote_address'),'override_price_info', $overrideInfo);
}
$entityTypeId = $installer->getEntityTypeId('catalog_product');

$attributeSetArr = $installer->getConnection()->fetchAll("SELECT attribute_set_id FROM {$this->getTable('eav_attribute_set')} WHERE entity_type_id={$entityTypeId}");

$attributeId = array($installer->getAttributeId($entityTypeId,'special_shipping_group'),
					 $installer->getAttributeId($entityTypeId,'shipping_price'));

foreach( $attributeSetArr as $attr)
{
	$attributeSetId= $attr['attribute_set_id'];

	$installer->addAttributeGroup($entityTypeId,$attributeSetId,'Shipping','99');

	$attributeGroupId = $installer->getAttributeGroupId($entityTypeId,$attributeSetId,'Shipping');

	foreach($attributeId as $att){
	$installer->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,$att,'99');
	}

};

$installer->endSetup();








