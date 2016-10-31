<?php

class Exinent_Productlock_Model_Resource_ProductLock extends Mage_Core_Model_Mysql4_Abstract {

    protected function _construct() {
        $this->_init('productlock/productlock', 'id');
    }

}
