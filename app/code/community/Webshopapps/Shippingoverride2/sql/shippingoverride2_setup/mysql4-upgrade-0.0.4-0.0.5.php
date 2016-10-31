<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER IGNORE TABLE {$this->getTable('shippingoverride2')}  
    ADD item_weight_from_value decimal(12,4) NULL default '0.0000',
    ADD item_weight_to_value decimal(12,4) NULL default '1000000',
    DROP INDEX `dest_country`;

 
");

$installer->endSetup();


