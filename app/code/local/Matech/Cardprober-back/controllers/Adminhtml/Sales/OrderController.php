<?php
require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml').DS."Sales/OrderController.php";  
class Matech_Cardprober_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController{
    function efraudCardproberAction() {
		if ($order = $this->_initOrder()) {
			 try {
				 $order->setefraudcardprober()
					->save();
				 $this->_getSession()->addSuccess(
					$this->__('The order has been set to eFraud CardProber.')
				 );
			  }
			  catch (Mage_Core_Exception $e) {
				 $this->_getSession()->addError($e->getMessage());
			  }
			  catch (Exception $e) {
				 $this->_getSession()->addError($this->__('The order has not been set to MyState.'));
				 Mage::logException($e);
			  }
			  $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    		}	
    
    }
}
				