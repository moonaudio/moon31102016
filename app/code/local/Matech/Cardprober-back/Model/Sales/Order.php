<?php
class Matech_Cardprober_Model_Sales_Order extends Mage_Sales_Model_Order
{
      const EFRAUD_CARDPROBER     = 'efraud_cardprober'; //Name of the State
      
        // const EFRAUD_CARDPROBER     = 'efraud_cardprober'; //Name of the State
    public function setefraudcardprober()
    {
	     
        if ($this->canCancel()) { //this line is optional
              $this->setState(self::EFRAUD_CARDPROBER, true);
        }

        return $this;
    }
}
		