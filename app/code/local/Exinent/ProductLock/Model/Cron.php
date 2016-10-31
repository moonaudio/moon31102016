<?php

class Exinent_ProductLock_Model_Cron {

    public function unlockProducts() {
        Mage::helper('productlock')->productsExpire();
    }

}
