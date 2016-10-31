<?php
    $installer = $this;

    // Required tables
    $statusTable = $installer->getTable('sales/order_status');
    $statusStateTable = $installer->getTable('sales/order_status_state');

    // Insert statuses
    $installer->getConnection()->insertArray(
        $statusTable,
        array('status', 'label'),
        array(
            array(
                'status' => 'efraud_cardprober', 
                'label' => 'eFraud Cardprober'
            )
        )
    );

    // Insert states and mapping of statuses to states
    $installer->getConnection()->insertArray(
        $statusStateTable,
        array('status', 'state', 'is_default'),
            array(
                array(
                    'status' => 'efraud_cardprober', 
                    'state' => 'efraud_cardprober', 
                    'is_default' => 1
                )
            )
        );