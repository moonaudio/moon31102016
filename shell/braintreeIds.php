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

require_once 'abstract.php';
class Mage_Shell_BraintreeIds extends Mage_Shell_Abstract
{
    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f braintreeIds.php
  help              This help

USAGE;
    }

    /**
     * Run script
     *
     */
    public function run()
    {    
        $credentials = array();
        $websites = Mage::getResourceModel('core/website_collection')->setLoadDefault(true)->load();
        // get unique credentials for all websites
        foreach ($websites as $website) {
            $merchantId = $website->getConfig('payment/braintree/merchant_id');
            if (!array_key_exists($merchantId, $credentials)) {
                $credentials[$merchantId] = array(
                    'public_key'    => $website->getConfig('payment/braintree/public_key'),
                    'private_key'   => $website->getConfig('payment/braintree/private_key'),
                    'environment'   => $website->getConfig('payment/braintree/environment'));
            }
        }
        if (!$credentials) {
            die ('No credentials found');
        }

        $braintree = Mage::getSingleton('braintree_payments/paymentmethod');
        $customerResourceModel = Mage::getResourceModel('customer/customer');
        foreach ($credentials as $merchantId => $additionalData) {
            Braintree_Configuration::environment($additionalData['environment']);
            Braintree_Configuration::merchantId($merchantId);
            Braintree_Configuration::publicKey($additionalData['public_key']);
            Braintree_Configuration::privateKey($additionalData['private_key']);
            // load all Braintree customers for merchant account
            try {
                $customers = Braintree_Customer::all();
            } catch (Exception $e) {
                echo 'Verify credentials for merchant id "' . $merchantId . '"\n';
                continue;
            }
            echo 'For merchant account ' . $merchantId .' found ' . $customers->maximumCount() . 
                ' customers. Processing...\n';
            $counter = 0;
            // Updating customer id if not updated
            foreach ($customers as $customer) {
                // If customer has email entered in Braintree than check combination email + id. 
                // If the same combination found in Magento - update id
                if ($customer->email) {
                    $adapter = $customerResourceModel->getReadConnection();
                    $bind    = array('customer_email' => $customer->email);
                    $select  = $adapter->select()
                        ->from($customerResourceModel->getEntityTable(), 
                            array($customerResourceModel->getEntityIdField()))
                        ->where('email = :customer_email');
                    $customerId = $adapter->fetchOne($select, $bind);
                    if ($customerId == $customer->id) {
                        $this->_updateCustomer($customerId, $customer->email);
                    }
                // If customer doesn't have email entered in Braintree than search in Braintree generated customer id
                // If not found - update customer id    
                } else {
                    $customerModel = Mage::getModel('customer/customer')->load($customer->id);
                    if ($customerModel->getId()) {
                        $id = $braintree->generateCustomerId($customerModel->getId(), $customerModel->getEmail());
                        try {
                            Braintree_Customer::find($id);
                        } catch (Exception $e) {
                            $this->_updateCustomer($customerModel->getId(), $customerModel->getEmail(), $id);
                        }
                    }
                }
                $counter++;
                if (($counter % 10) == 0) {
                    echo 'Processed ' . $counter . ' customers\n';
                }
            }
            echo 'Processed all customers for merchant account "' . $merchantId .'"\n';
        }
        echo 'All customers for all websites processed\n';
    }

    /**
     * Updates customer id
     * 
     * @param string $customerId
     * @param string $email
     */
    protected function _updateCustomer($customerId, $email, $newId = false)
    {
        $braintree = Mage::getSingleton('braintree_payments/paymentmethod');
        if (!$newId) {
            $newId = $braintree->generateCustomerId($customerId, $email);
        }
        try {
            Braintree_Customer::update($customerId, array('id' => $newId));
        } catch (Exception $e) {
            Mage::logException($e);
        }        
    }
}

$braintreeIds = new Mage_Shell_BraintreeIds();
$braintreeIds->run();
