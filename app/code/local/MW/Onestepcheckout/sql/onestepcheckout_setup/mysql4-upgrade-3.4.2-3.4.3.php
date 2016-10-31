<?php

$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE {$this->getTable('sales/order')}
    ADD COLUMN `giftwrap_discount` decimal(12,4) default NULL,
    ADD COLUMN `base_giftwrap_discount` decimal(12,4) default NULL;
");

$installer->endSetup();