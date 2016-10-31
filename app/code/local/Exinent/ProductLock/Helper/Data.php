<?php

class Exinent_ProductLock_Helper_Data extends Mage_Core_Helper_Abstract {
    public function productsExpire()
    {
        $productLockCollection = Mage::getModel('productlock/productlock')->getCollection();
        foreach($productLockCollection as $productlock)
        {
            $expiry = $productlock->getExpiry();
            if($expiry - time() < 0)
            {
                $productlock->delete();
            }
        }
    }
    public function userProductsExpire($user)
    {
        $productLockCollection = Mage::getModel('productlock/productlock')->getCollection();
        foreach($productLockCollection as $productlock)
        {
            $loggedUser = $productlock->getLoggedUser();
            if($loggedUser == $user)
            {
                $productlock->delete();
            }
        }
    }
}
