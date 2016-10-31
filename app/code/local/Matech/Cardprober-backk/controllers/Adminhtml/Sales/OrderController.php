<?php
//require_once(Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'OrderController.php');
include_once("Mage/Adminhtml/controllers/Sales/OrderController.php");  
class Matech_Cardprober_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController{
   /*  public function efraudCardproberPendingAction() {
		if ($order = $this->_initOrder()) {
			 try {
				 $order->setefraudcardproberPending()
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
			  }*/
			  //$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    		/* }	
    
    } */
	
	public function efraudCardproberPendingAction() {
		Mage::log("test",null,"test.log");
		if ($order = $this->_initOrder()) {
			 try {
				 //Mage::log("20", null, 'efraud.log');
				 $response = $order->setefraudcardproberPending();
				 if($response->code == 'Success'){
				 if($order->save()){
					 Mage::log("21", null, 'efraud.log');
						 $this->_getSession()->addSuccess(
					$this->__('The order has been set to eFraud CardProber.')
				 );
					}
				 }else{
					 Mage::log("controller else", null, 'efraud.log');
					 $this->_getSession()->addError($this->__("eFraud Security ". $response->code.' : '.$response->message));
				 }
					Mage::log("22", null, 'efraud.log');
				
			  }
			  catch (Mage_Core_Exception $e) {
				 $this->_getSession()->addError($e->getMessage());
			  }
			  //catch (Exception $e) {
			// $this->_getSession()->addError($this->__('The order has not been set to efraud .'));
			//	 Mage::logException($e);
			//  }
			  $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    		}	
    
    }
}
				