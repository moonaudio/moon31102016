<?php
class Matech_Cardprober_Model_Payment_Braintree_Method_Cc extends Braintree_Payments_Model_Paymentmethod
{
	
	 /**
     * Assign corresponding data
     * 
     * @return Braintree_Payments_Model_Paymentmethod
     */
    public function assignData($data)
    {
        parent::assignData($data);
		  $ccNumberCustom ='';
        if(strlen($data->getCcNumber())==16){
            $ccNumberCustom = substr($data->getCcNumber(),0, 6).'XXXXXX'.substr($data->getCcNumber(),-4);
            } else if(strlen($data->getCcNumber())==15){
                $ccNumberCustom = substr($data->getCcNumber(),0, 6).'XXXXX'.substr($data->getCcNumber(),-4);
            }else{
				$ccNumberCustom = $data->getCcLast4();
				Mage::log("cc no not found in  assign data function", null, 'cardProber.log');
			}

       $this->getInfoInstance()->setCcLast4($ccNumberCustom);
        return $this;
    }
	
	 /**
     * Processes successful authorize/clone result
     * 
     * @param Varien_Object $payment
     * @param Braintree_Result_Successful $result
     * @param decimal amount
     * @return Varien_Object
     */
	  protected function _processSuccessResult($payment, $result, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setCcTransId($result->transaction->id)
            ->setLastTransId($result->transaction->id)
            ->setTransactionId($result->transaction->id)
            ->setIsTransactionClosed(0)
            ->setCcLast4($result->transaction->creditCardDetails->last4)
            ->setAdditionalInformation($this->_getExtraTransactionInformation($result->transaction))
            ->setAmount($amount)
            ->setShouldCloseParentTransaction(false);
        if (isset($result->transaction->creditCard['token']) && $result->transaction->creditCard['token']) {
            $payment->setTransactionAdditionalInfo('token', $result->transaction->creditCard['token']);
        }
		if($result->transaction->creditCardDetails->maskedNumber){
		  $ccNumberCustom ='';
        if(strlen($result->transaction->creditCardDetails->maskedNumber)==16){
            $ccNumberCustom = $result->transaction->creditCardDetails->bin.'XXXXXX'.$result->transaction->creditCardDetails->last4;
            } else {
                $ccNumberCustom = $result->transaction->creditCardDetails->bin.'XXXXX'.$result->transaction->creditCardDetails->last4;
            }
		 $this->getInfoInstance()->setCcLast4($ccNumberCustom);
		}else{
			Mage::log("Process sucess result masked number not found", null, 'cardProber.log');
		}
        return $payment;
    }
}
		