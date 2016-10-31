<?php

$installer = $this;

$installer->startSetup();

if  (Mage::helper('wsacommon')->getNewVersion() != 1.6) {

    "ALTER IGNORE TABLE {$this->getTable('sales_flat_quote_address')}  ADD override_price_info varchar(255);";

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

    

$installer->endSetup();