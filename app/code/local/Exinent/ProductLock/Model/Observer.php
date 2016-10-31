<?php

class Exinent_ProductLock_Model_Observer {

    public function lockProduct(Varien_Event_Observer $observer) {
        $productObject = $observer->getEvent()->getProduct();
        $productLockData = Mage::getModel('productlock/productlock')->getCollection();
        $productLockData->addFieldToFilter('product_id', $productObject->getId());
        $productData = $productLockData->getData();
        if (!empty($productData)) {
            $productLockObject = Mage::getModel('productlock/productlock')->load($productData[0]['id']);
        } else {
            $productLockObject = Mage::getModel('productlock/productlock');
        }
        if ($productLockObject->getLoggedUser() != '' && $productLockObject->getLoggedUser() != Mage::getSingleton('admin/session')->getUser()->getUsername()) {
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl('*/*/index/e/' . $productLockObject->getId() . '/'));
        }
        if ($productLockObject->getLoggedUser() == '' || $productLockObject->getLoggedUser() == Mage::getSingleton('admin/session')->getUser()->getUsername()) {
            $productLockObject->setProductId($productObject->getId());
            $productLockObject->setLoggedUser(Mage::getSingleton('admin/session')->getUser()->getUsername());
            $productLockObject->setExpiry(time()+900);
            $productLockObject->save();
        }
    }

    public function unlockProduct(Varien_Event_Observer $observer) {
        $productObject = $observer->getEvent()->getProduct();
        $productLockData = Mage::getModel('productlock/productlock')->getCollection();
        $productLockData->addFieldToFilter('product_id', $productObject->getId());
        $productData = $productLockData->getData();
        $productLockObject = Mage::getModel('productlock/productlock')->load($productData[0]['id']);
        $productLockObject->delete();
    }

    public function displayError(Varien_Event_Observer $observer) {
        $block = $observer->getBlock();
        if (!isset($block)) {
            return $this;
        }
        if ($block->getType() == 'adminhtml/catalog_product_grid') {
            Mage::helper('productlock')->productsExpire();
            $lockId = Mage::app()->getRequest()->getParam('e');
            $productLockObject = Mage::getModel('productlock/productlock')->load($lockId);
            if (Mage::app()->getRequest()->getParam('e') != '') {
                Mage::getSingleton('adminhtml/session')->addError('This Product is currenty opened by ' . $productLockObject->getLoggedUser());
            }
        }
    }

}
