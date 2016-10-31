<?php

$installer = $this;

$installer->startSetup();

$installer->run("


 ALTER TABLE `shippingoverride2` DROP INDEX `dest_country` ,
ADD UNIQUE `dest_country` ( `website_id` , `dest_country_id` , `dest_region_id` , `dest_city` , `dest_zip` , `dest_zip_to` , `special_shipping_group` , `weight_from_value` , `weight_to_value` , `price_from_value` , `price_to_value` , `item_from_value` , `item_to_value` , `delivery_type` , `customer_group` , `algorithm` ) 
	
");

$installer->endSetup();


