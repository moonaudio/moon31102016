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
  weight_from_value decimal(12,4) NOT NULL default '0.0000',
  weight_to_value decimal(12,4) NOT NULL default '0.0000',
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
  PRIMARY KEY(`pk`),
  UNIQUE KEY `dest_country` (`website_id`,`dest_country_id`,`dest_region_id`,`dest_city`,`dest_zip`,`dest_zip_to`,`special_shipping_group`,`weight_from_value`,`weight_to_value`,`price_from_value`,`price_to_value`,`item_from_value`,`item_to_value`,`delivery_type`,`customer_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


select @entity_type_id:=entity_type_id from {$this->getTable('eav_entity_type')} where entity_type_code='catalog_product';
select @attribute_set_id:=attribute_set_id from {$this->getTable('eav_attribute_set')} where entity_type_id=@entity_type_id and attribute_set_name='Default';

insert ignore into {$this->getTable('eav_attribute_group')} 
    set attribute_set_id 	= @attribute_set_id,
	attribute_group_name	= 'Shipping',
	sort_order		= 99;

select @attribute_group_id:=attribute_group_id from {$this->getTable('eav_attribute_group')} where attribute_group_name='Shipping';

insert ignore into {$this->getTable('eav_attribute')}
    set entity_type_id 	= @entity_type_id,
	attribute_code 	= 'special_shipping_group',
	backend_type	= 'int',
	frontend_input	= 'select',
	is_required	= 0,
	is_user_defined	= 1,
	used_in_product_listing	= 1,
	is_filterable_in_search	= 0,
	frontend_label	= 'Special Shipping Group';

select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where attribute_code='special_shipping_group';


insert ignore into {$this->getTable('eav_entity_attribute')} 
    set entity_type_id 		= @entity_type_id,
	attribute_set_id 	= @attribute_set_id,
	attribute_group_id	= @attribute_group_id,
	attribute_id		= @attribute_id;

	
insert ignore into {$this->getTable('eav_attribute')} 
    set entity_type_id 	= @entity_type_id,
    	attribute_code 	= 'shipping_price',
    	backend_type	= 'decimal',
    	frontend_input	= 'price',
    	is_required	= 0,
    	is_user_defined	= 1,
    	used_in_product_listing = 1,
    	is_filterable_in_search	= 0,
    	frontend_label	= 'Shipping Price';
    	
select @attribute_id:=attribute_id from {$this->getTable('eav_attribute')} where frontend_label='Shipping Price';
 	
insert ignore into {$this->getTable('eav_entity_attribute')} 
    set entity_type_id 		= @entity_type_id,
	attribute_set_id 	= @attribute_set_id,
	attribute_group_id	= @attribute_group_id,
	attribute_id		= @attribute_id;

		
	
");

$installer->endSetup();


