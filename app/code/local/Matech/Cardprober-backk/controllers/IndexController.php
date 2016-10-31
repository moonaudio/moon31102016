<?php 

class Matech_Cardprober_IndexController extends Mage_Core_Controller_Front_Action {
    public function cardreponseAction() {
      
        $requestParam = $this->getRequest()->getParams();
	
        if($this->getRequest()->getParam('token')==Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/token')){
            Mage::log(print_r($this->getRequest()->getRawBody(), true),null, 'efraudprint.log');
			//print_r($this->getRequest()->getParam());
			//print_r($this->getRequest()->getPost());
			echo "doe";
	/*		if ($this->getRequest()->isPost() && $this->getRequest()->getHeader('Content-Type') == 'text/xml') { 
		
    $postedXml = $this->getRequest()->getRawBody();
    var_dump($postedXml);
	
} */
			
        }else{
			 Mage::log("Access denied ". $_SERVER['REMOTE_ADDR'],null, 'efraudprint.log');
			//echo "Access denied";
		}
        
		
       
        //echo 'Setup!';
    }
	 public function saveFraudStatusAction(){
        $statuslog = Mage::getModel('cardprober/cardprober');
        $statuslog->setData('order_id', 'asdasd');
        if($response->code == 'Success'){
                   $statuslog->setData('status', 'Submitted');
             }
   
        $statuslog->setData('message', 'asdasd');
        $statuslog->setData('status_flag', '0');
        $statuslog->save();
    }  
}