<?php

$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')}  ADD override_price_info TEXT;
");

$installer->endSetup();


