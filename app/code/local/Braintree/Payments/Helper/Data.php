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

class Braintree_Payments_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CONFIG_PATH_KOUNT_ID = 'payment/braintree/kount_id';

    protected $_today = null;

    /**
     * Finds credit card type by type name using global payments config
     * 
     * @param string $name
     * @return boolean | string
     */
    public function getCcTypeCodeByName($name)
    {
        $ccTypes = Mage::getConfig()->getNode('global/payment/cc/types')->asArray();
        foreach ($ccTypes as $code => $data) {
            if (isset($data['name']) && $data['name'] == $name) {
                return $code;
            }
        }
        return false;
    }

    /**
     * Get the configured Kount ID
     *
     * @return mixed
     */
    public function getKountId()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_KOUNT_ID);
    }

    /**
     * Removes Magento added transaction id suffix if applicable
     * 
     * @param string $transactionId
     * @return strung
     */
    public function clearTransactionId($transactionId)
    {
        $suffixes = array(
            '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
            '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID,
        );
        foreach ($suffixes as $suffix) {
            if (strpos($transactionId, $suffix) !== false) {
                $transactionId = str_replace($suffix, '', $transactionId);
            }        
        }
        return $transactionId;
    }

    /**
     * Returns today year
     * 
     * @return string
     */
    public function getTodayYear()
    {
        if (!$this->_today) {
            $this->_today = Mage::app()->getLocale()->date(Mage::getSingleton('core/date')->gmtTimestamp(), null, null);
        }
        return $this->_today->toString('Y');
    }

    /**
     * Returns today month
     * 
     * @return string
     */
    public function getTodayMonth()
    {
        if (!$this->_today) {
            $this->_today = Mage::app()->getLocale()->date(Mage::getSingleton('core/date')->gmtTimestamp(), null, null);
        }
        return $this->_today->toString('M');
    }
}
