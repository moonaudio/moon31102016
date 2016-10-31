<?php
/**
 * Matech_Cardprober extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category       Matech
 * @package        Matech_Cardprober
 * @copyright      Copyright (c) 2016
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Cardprober module install script
 *
 * @category    Matech
 * @package     Matech_Cardprober
 * @author      
 */
$this->startSetup();
$table = $this->getConnection()
    ->newTable($this->getTable('matech_cardprober/cardprober'))
    ->addColumn(
        'entity_id',
        Varien_Db_Ddl_Table::TYPE_INTEGER,
        null,
        array(
            'identity'  => true,
            'nullable'  => false,
            'primary'   => true,
        ),
        'Card Prober ID'
    )
    ->addColumn(
        'order_id',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable'  => false,
        ),
        'Order Id'
    )
    ->addColumn(
        'status',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable'  => false,
        ),
        'Status'
    )
    ->addColumn(
        'message',
        Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        array(
            'nullable'  => false,
        ),
        'Message'
    )
  
    ->addColumn(
        'updated_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(),
        'Card Prober Modification Time'
    )
    ->addColumn(
        'created_at',
        Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null,
        array(),
        'Card Prober Creation Time'
    ) 
    ->setComment('Card Prober Table');
$this->getConnection()->createTable($table);
$this->endSetup();
