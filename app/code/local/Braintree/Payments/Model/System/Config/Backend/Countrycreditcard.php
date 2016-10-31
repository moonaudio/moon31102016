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

class Braintree_Payments_Model_System_Config_Backend_Countrycreditcard extends Mage_Core_Model_Config_Data
{
    /**
     * Prepare data before save
     */
    protected function _beforeSave()
    {
        $value = $this->getValue();
        $result = array();
        foreach ($value as $hash => $data) {
            if (!$data) {
                continue;
            }
            if (!is_array($data)) {
                continue;
            }
            if (count($data) < 2) {
                continue;
            }
            $country = $data['country_id'];
            if (array_key_exists($country, $result)) {
                $result[$country] = array_unique(array_merge($result[$country], $data['cc_types']));
            } else {
                $result[$country] = $data['cc_types'];
            }
        }
        $this->setValue(serialize($result));
    }

    /**
     * Process data after load
     */
    protected function _afterLoad()
    {
        $value = unserialize($this->getValue());
        $this->setValue($value);
    }
}
