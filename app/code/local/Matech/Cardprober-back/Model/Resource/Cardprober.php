<?php

class Matech_Cardprober_Model_Resource_Cardprober extends Mage_Core_Model_Resource_Db_Abstract
{

    public function _construct()
    {
        $this->_init('cardprober/cardprober', 'entity_id');
    }
}
