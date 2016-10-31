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

class Braintree_Payments_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Return credit cart type
     * 
     * @return string
     */
    protected function getCcTypeName()
    {
        $types = Mage::getSingleton('payment/config')->getCcTypes();
        $ccType = $this->getInfo()->getCcType();
        if (isset($types[$ccType])) {
            return $types[$ccType];
        } else {
            return Mage::helper('braintree_payments')->__('Stored Card');
        }
    }

    /**
     * Prepare information specific to current payment method
     * 
     * @param null | array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = array();
        $info = $this->getInfo();
        if ($ccType = $this->getCcTypeName()) {
            $data[Mage::helper('braintree_payments')->__('Credit Card Type')] = $ccType;
        }
        if ($info->getCcLast4()) {
            $data[Mage::helper('braintree_payments')->__('Credit Card Number')] = 
                sprintf('xxxx-%s', $info->getCcLast4());
        }
        if (Mage::app()->getStore()->isAdmin() && $info->getAdditionalInformation()) {
            foreach ($info->getAdditionalInformation() as $field => $value) {
                $beautifiedFieldName = ucwords(trim(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $field)));
                $data[Mage::helper('braintree_payments')->__($beautifiedFieldName)] = $value;
            }
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }

    /**
     * Retrieve child block HTML
     *
     * @param   string $name
     * @param   boolean $useCache
     * @param   boolean $sorted
     * @return  string
     */
    public function getChildHtml($name = '', $useCache = true, $sorted = false) {
        $payment = $this->getRequest()->getPost('payment');
        $result = "";
        $deviceData = $this->getRequest()->getPost('device_data');

        if (isset($payment["cc_token"]) && $payment["cc_token"]) {
            $cc_token = $payment["cc_token"];
            $result .= "<input type='hidden' name='payment[cc_token]' value='$cc_token'>";
        }
        if (isset($payment['store_in_vault']) && $payment['store_in_vault']) {
            $storeInVault = $payment['store_in_vault'];
            $result .= "<input type='hidden' name='payment[store_in_vault]' value='$storeInVault'>";
        }
        if ($deviceData) {
            $result .= "<input type='hidden' name='device_data' value='$deviceData'>";
        }
        return $result;
    }
}
