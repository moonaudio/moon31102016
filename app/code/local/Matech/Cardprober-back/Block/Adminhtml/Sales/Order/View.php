<?php
class Matech_Cardprober_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
      public function __construct()
    {
        parent::__construct();
       
        // New Action for eFraud Security Card Prober
         $order = $this->getOrder();
          
          if ($this->_isAllowedAction('ship') && $order->canShip()
            && !$order->getForcedDoShipmentWithInvoice() && $order->getStatusLabel() != 'eFraud Cardprober' ) {
        $this->_addButton('efraud_cardprober', array(
			'label'     => Mage::helper('sales')->__('eFraud Cardprober'),
			'onclick'   => 'setLocation(\'' . $this->getEfraudCardprober() . '\')',
		));
            }
        
    }
     function getEfraudCardprober() {
		// Here we are telling the name of Action which will be
		// triggered when the button is clicked
		return $this->getUrl('*/*/efraudCardprober');
   }

}
			