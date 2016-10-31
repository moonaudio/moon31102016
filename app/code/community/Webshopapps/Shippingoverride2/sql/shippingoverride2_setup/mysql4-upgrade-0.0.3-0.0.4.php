<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER IGNORE TABLE {$this->getTable('shippingoverride2')} ADD rules varchar(255) NULL;
 
");

$installer->endSetup();


