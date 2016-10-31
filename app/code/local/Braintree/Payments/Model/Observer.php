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

class Braintree_Payments_Model_Observer
{
    const CONFIG_PATH_CAPTURE_ACTION    = 'payment/braintree/capture_action';
    const CONFIG_PATH_PAYMENT_ACTION    = 'payment/braintree/payment_action';
    
    /**
     * If it's configured to capture on shipment - do this
     * 
     * @param Varien_Event_Observer $observer
     * @return Braintree_Payments_Model_Observer
     */
    public function processBraintreePayment(Varien_Event_Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        if ($order->getPayment()->getMethod() == Braintree_Payments_Model_Paymentmethod::PAYMENT_METHOD_CODE 
            && $order->canInvoice() && $this->_shouldInvoice()) {

            $qtys = array(); 
            foreach ($shipment->getAllItems() as $shipmentItem) {
                $qtys[$shipmentItem->getOrderItem()->getId()] = $shipmentItem->getQty();
            }
            foreach ($order->getAllItems() as $orderItem) {
                if (!array_key_exists($orderItem->getId(), $qtys)) {
                    $qtys[$orderItem->getId()] = 0;
                }
            }
            $invoice = $order->prepareInvoice($qtys);
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
        }
        return $this;
    }

    /**
     * If it's configured to capture on each shipment
     * 
     * @return boolean
     */
    protected function _shouldInvoice()
    {
        return ((Mage::getStoreConfig(self::CONFIG_PATH_PAYMENT_ACTION) == 
            Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE) && 
            (Mage::getStoreConfig(self::CONFIG_PATH_CAPTURE_ACTION) == 
            Braintree_Payments_Model_Paymentmethod::CAPTURE_ON_SHIPMENT));
    }

    /**
     * Delete Braintree customer when Magento customer is deleted
     * 
     * @param Varien_Event_Observer $observer
     * @return Braintree_Payments_Model_Observer
     */
    public function deleteBraintreeCustomer(Varien_Event_Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $braintree = Mage::getModel('braintree_payments/paymentmethod');
        $customerId = $braintree->generateCustomerId($customer->getId(), $customer->getEmail());
        if ($braintree->exists($customerId)) {
            $braintree->deleteCustomer($customerId);
        }
        return $this;
    }
}
