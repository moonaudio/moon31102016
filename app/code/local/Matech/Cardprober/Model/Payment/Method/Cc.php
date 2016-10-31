<?php
class Matech_Cardprober_Model_Payment_Method_Cc extends Mage_Payment_Model_Method_Cc
{
	  public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        /* For customize cradit card number for comment history */
        $ccNumberCustom ='';
        if(strlen($data->getCcNumber())==16){
            $ccNumberCustom = substr($data->getCcNumber(),0, 6).'XXXXXX'.substr($data->getCcNumber(),-4);
            } else {
                $ccNumberCustom = substr($data->getCcNumber(),0, 6).'XXXXX'.substr($data->getCcNumber(),-4);
            }
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            //->setCcLast4(substr($data->getC   cNumber(), -4))   code comment for cc number 
            //->setCcLast4($data->getCcNumber())   // code change for cc number  complete showing
			->setCcLast4($ccNumberCustom)
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcSsIssue($data->getCcSsIssue())
            ->setCcSsStartMonth($data->getCcSsStartMonth())
            ->setCcSsStartYear($data->getCcSsStartYear())
            ;
        return $this;
    }
}
		