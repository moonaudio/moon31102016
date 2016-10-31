<?php
class Matech_Cardprober_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
      public function __construct()
    {
        parent::__construct();
       
       
         $order = $this->getOrder();
		 //$payment = $this->getPayment()->debug();
         Mage::log(print_r($this->getPayment(), true), null, 'efraud.log');
          if ($this->_isAllowedAction('ship') && $order->canShip()
            && !$order->getForcedDoShipmentWithInvoice() && $order->getStatusLabel() != 'eFraud Pending'  && Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/status') ) {
        $this->_addButton('efraud_cardprober', array(
			'label'     => Mage::helper('sales')->__('Send To Cardprober'),
			'onclick'   => 'setLocation(\'' . $this->getEfraudCardproberPending() . '\')',
		));
            }
        
    }
     function getEfraudCardproberPending() {
		// Here we are telling the name of Action which will be
		// triggered when the button is clicked
		return $this->getUrl('*/order/efraudCardproberPending');
   }

}
			