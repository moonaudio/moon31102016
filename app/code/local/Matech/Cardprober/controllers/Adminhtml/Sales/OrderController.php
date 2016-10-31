<?php

include_once("Mage/Adminhtml/controllers/Sales/OrderController.php");  
class Matech_Cardprober_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController{
	public function efraudCardproberPendingAction() {
		if ($order = $this->_initOrder()) {
			 try {
				
				 $response = $order->setefraudcardproberPending();
				 if($response->code == 'Success'){
				 if($order->save()){
						 $this->_getSession()->addSuccess(
					$this->__('The order has been set to eFraud CardProber.')
				 );
					}
				 }else{
					 $this->_getSession()->addError($this->__("eFraud Security ". $response->code.' : '.$response->message));
				 }
				
			  }
			  catch (Mage_Core_Exception $e) {
				 $this->_getSession()->addError($e->getMessage());
			  }
			  $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    		}	
    
    }
}
				