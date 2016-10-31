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

class Braintree_Payments_Model_Source_Environment
{
    const ENVIRONMENT_PRODUCTION    = 'production';
    const ENVIRONMENT_SANDBOX       = 'sandbox';

    /**
     * Possible environment types
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::ENVIRONMENT_SANDBOX,
                'label' => 'Sandbox',
            ),
            array(
                'value' => self::ENVIRONMENT_PRODUCTION,
                'label' => 'Production'
            )
        );
    }
}
