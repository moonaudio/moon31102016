<?php

require_once('Mage/Adminhtml/controllers/IndexController.php');

class Exinent_ProductLock_Adminhtml_IndexController extends Mage_Adminhtml_IndexController {

    public function logoutAction() {
        /** @var $adminSession Mage_Admin_Model_Session */
        $adminSession = Mage::getSingleton('admin/session');
        if($adminSession->getUser() != '') {
			Mage::helper('productlock')->userProductsExpire($adminSession->getUser()->getUsername());
        }
		$adminSession->unsetAll();
        $adminSession->getCookie()->delete($adminSession->getSessionName());
        $adminSession->addSuccess(Mage::helper('adminhtml')->__('You have logged out.'));

        $this->_redirect('*');
    }

}
