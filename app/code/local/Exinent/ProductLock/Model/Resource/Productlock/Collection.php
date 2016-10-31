<?php
class Exinent_Productlock_Model_Resource_Productlock_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('productlock/productlock');
    }
}