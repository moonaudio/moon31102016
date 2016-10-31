<?php
/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2014 Braintree. (https://www.braintreepayments.com/)
*/

class Braintree_Payments_Model_Source_CaptureAction
{
    /**
     * Possible actions to capture
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Braintree_Payments_Model_Paymentmethod::CAPTURE_ON_INVOICE,
                'label' => 'Invoice'
            ),
            array(
                'value' => Braintree_Payments_Model_Paymentmethod::CAPTURE_ON_SHIPMENT,
                'label' => 'Shipment'
            ),            
        );
    }
}
