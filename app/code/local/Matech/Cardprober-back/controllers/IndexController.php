<?php 

class Matech_Cardprober_IndexController extends Mage_Core_Controller_Front_Action {
    public function cardreponseAction() {
        
        $requestParam = $this->getRequest()->getParams();
        $requestParamPost = $this->getRequest()->getPost();
       // echo $this->getRequest()->getParam('token')."<br/>";
        // print_r($this->getRequest()->getParams());
        if($this->getRequest()->getParam('token')=='abcdefasdasdasdasd'){
            print_r($this->getRequest()->getParams());
        }
        
       
        //echo 'Setup!';
    }
}