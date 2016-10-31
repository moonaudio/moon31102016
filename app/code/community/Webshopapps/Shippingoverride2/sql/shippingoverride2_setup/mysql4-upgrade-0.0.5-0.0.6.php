<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER IGNORE TABLE {$this->getTable('shippingoverride2')}  
    ALTER item_weight_to_value SET default '1000000';

 
");

$installer->endSetup();


