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
                'status' => 'efraud_pending', 
                'label' => 'eFraud Pending'
            ),
             array(
                'status' => 'efraud_awaiting_response', 
                'label' => 'eFraud Awaiting Response'
            ),
             array(
                'status' => 'efraud_removed_from_queue', 
                'label' => 'eFraud Removed from Queue'
            ),
             array(
                'status' => 'efraud_scoreOnly', 
                'label' => 'eFraud ScoreOnly'
            ),
             array(
                'status' => 'efraud_notinsured', 
                'label' => 'eFraud  NotInsured'
            ),
             array(
                'status' => 'efraud_allowed', 
                'label' => 'eFraud ALLOWED'
            ),
             array(
                'status' => 'efraud_rejected', 
                'label' => 'eFraud Rejected'
            ),
             array(
                'status' => 'efraud_fraud', 
                'label' => 'eFraud FRAUD'
            ),
             array(
                'status' => 'efraud_fraud_missed', 
                'label' => 'eFraud FRAUD-Missed'
            ),
             array(
                'status' => 'efraud_cancelled', 
                'label' => 'eFraud Cancelled'
            )
        )
    );

    // Insert states and mapping of statuses to states
    $installer->getConnection()->insertArray(
        $statusStateTable,
        array('status', 'state', 'is_default'),
                array(
            array(
                'status' => 'efraud_pending', 
                'state' => 'efraud_pending', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_awaiting_response', 
                'state' => 'efraud_awaiting_response', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_removed_from_queue', 
                'state' => 'efraud_removed_from_queue', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_scoreOnly', 
               'state' => 'efraud_scoreOnly', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_notinsured', 
                'state' => 'efraud_notinsured', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_allowed', 
                'state' => 'efraud_allowed', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_rejected', 
                'state' => 'efraud_rejected', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_fraud', 
                'state' => 'efraud_fraud', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_fraud_missed', 
                'state' => 'efraud_fraud_missed', 
                    'is_default' => 1
            ),
             array(
                'status' => 'efraud_cancelled', 
                'state' => 'efraud_cancelled', 
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
        
        