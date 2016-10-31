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
            ),
             array(
                'status' => 'efraud_pending', 
                'label' => 'eFraud Pending'
            ),
             array(
                'status' => 'efraud_approved', 
                'label' => 'eFraud Approved'
            ),
             array(
                'status' => 'efraud_rejected', 
                'label' => 'eFraud Rejected'
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
                ),
                 array(
                    'status' => 'efraud_pending', 
                    'state' => 'efraud_pending', 
                    'is_default' => 1
                ),
                 array(
                    'status' => 'efraud_approved', 
                    'state' => 'efraud_approved', 
                    'is_default' => 1
                ),
                 array(
                    'status' => 'efraud_rejected', 
                    'state' => 'efraud_rejected', 
                    'is_default' => 1
                )
            )
        );
    
    $installer->startSetup();
    $installer->run("-- DROP TABLE IF EXISTS {$this->getTable('matech_cardprober_cardprober')};
    CREATE TABLE {$this->getTable('matech_cardprober_cardprober')} (
	entity_id int(11) NOT NULL,
         order_id varchar(44) NOT NULL,
         status varchar(11) NOT NULL,
         message text NOT NULL,
         status_flag int(11) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	ALTER TABLE {$this->getTable('matech_cardprober_cardprober')}
  ADD PRIMARY KEY (`entity_id`);   ");
	$installer->endSetup();
        
        