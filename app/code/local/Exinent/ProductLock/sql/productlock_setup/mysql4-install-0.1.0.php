<?php

$installer = $this;
$installer->startSetup();
$table = $installer->getConnection()
        ->newTable($installer->getTable('productlock/productlock'))
        ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
                ), 'Id')
        ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'nullable' => false,
                ), 'Product Id')
        ->addColumn('logged_user', Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(
            'nullable' => false,
                ), 'User')
        ->addColumn('expiry', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
    'nullable' => false,
        ), 'expiry');
$installer->getConnection()->createTable($table);
$installer->endSetup();
?>