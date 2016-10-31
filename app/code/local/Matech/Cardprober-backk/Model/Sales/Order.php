<?php
class Matech_Cardprober_Model_Sales_Order extends Mage_Sales_Model_Order
{
      const EFRAUD_CARDPROBER  = 'efraud_cardprober'; //Name of the State
      const EFRAUD_PENDING     = 'efraud_pending';
      const EFRAUD_APPROVED    = 'efraud_approved';
      const EFRAUD_REJECTED    = 'efraud_rejected';
      
        // const EFRAUD_CARDPROBER     = 'efraud_cardprober'; //Name of the State
   
    public function saveFraudStatus($response){
        $statuslog = Mage::getModel('cardprober/cardprober');
		// Mage::log(print_r($response, true), null, 'efraud.log');
		//  Mage::log(print_r(), null, 'efraud.log');
        $statuslog->setData('order_id', $this->getIncrementId());
        if($response->code == 'Success'){
                   $statuslog->setData('status', 'Submitted');
             }
      // Mage::log("response mesage ".$response->message, null, 'efraud.log');
        $statuslog->setData('message', $response->message);
        $statuslog->setData('status_flag', '0');
        $statuslog->save();
    }  
      
    public function setefraudcardproberPending()
    {    
         //if ($this->canCancel()) {
		
            $efraudResponse = $this->sendToFraud();
            $response = new SimpleXMLElement($efraudResponse);
			//Mage::log(print_r($response, true), null, 'efraud.log');
             if($response->code == 'Success'){
				// Mage::log("15", null, 'efraud.log');
                 $this->setState(self::EFRAUD_PENDING, true);
			//	 Mage::log("16", null, 'efraud.log');
                 $this->saveFraudStatus($response);
			//	 Mage::log("17", null, 'efraud.log');
				 $this->addStatusHistoryComment("eFraud Security ". $response->code.' : '.$response->message);
				
             }
			 return $response;
             
			// Mage::log("19", null, 'efraud.log');
       // }

        // return $this;
    }
    
    
 public function getOrderXml(){
	// Mage::log("1", null, 'efraud.log');
$billingAddress = $this->getBillingAddress();
//Mage::log(print_r($billingAddress, true), null, 'efraud.log');
$shippingAddress = $this->getShippingAddress();
 //Mage::log("2", null, 'efraud.log');
$payment = $this->getPayment();
 //Mage::log("3", null, 'efraud.log');
 // Mage::log(print_r($this->getPayment()->debug(), true), null, 'efraud.log');
$cards = $this->getPayment()->debug();
// Mage::log("4", null, 'efraud.log');
$request_xml = "<?xml version='1.0' encoding='utf-8'?>";
$request_xml .= "<orders><order><SiteName>".Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/sitename');
$request_xml .="</SiteName>";
$request_xml .= "<OrderDate>".date('Y-m-d',strtotime($this->getCreatedAt()))."</OrderDate>";
$request_xml .= "<OrderNumber>".$this->getIncrementId()."</OrderNumber>";
$request_xml .= "<IPAddress>".$this->getRemoteIp()."</IPAddress>";
$request_xml .= "<BillingFirstName>".$billingAddress->getFirstname()."</BillingFirstName>";
$request_xml .= "<BillingLastName>".$billingAddress->getLastname()."</BillingLastName>";
 //Mage::log("5", null, 'efraud.log');
$billStreet = $billingAddress->getStreet();
$request_xml .= "<BillingAddress1>".$billStreet[0]."</BillingAddress1>";
$request_xml .= "<BillingAddress1>".$billStreet[1]."</BillingAddress1>";
$request_xml .= "<BillingCity>".$billingAddress->getCity()."</BillingCity>";
$request_xml .= "<BillingState>".$billingAddress->getState()."</BillingState>";
$request_xml .= "<BillingZip>".$billingAddress->getPostcode()."</BillingZip>";
$request_xml .= "<BillingCountry>".$billingAddress->getCountry()."</BillingCountry>";
$request_xml .= "<BillingEveningPhone>".$billingAddress->getTelephone()."</BillingEveningPhone>";
$request_xml .= "<BillingEmail>".$billingAddress->getEmail()."</BillingEmail>";
$request_xml .= "<ShippingMethod>".$this->getShippingDescription()."</ShippingMethod>";
$request_xml .= "<ShippingFirstName>".$shippingAddress->getFirstname()."</ShippingFirstName>";
$request_xml .= "<ShippingLastName>".$shippingAddress->getLastname()."</ShippingLastName>";
$shipStreet = $shippingAddress->getStreet();
$request_xml .= "<ShippingAddress1>".$shipStreet[0]."</ShippingAddress1>";
$request_xml .= "<ShippingAddress2>".$shipStreet[1]."</ShippingAddress2>";
$request_xml .= "<ShippingCity>".$shippingAddress->getCity()."</ShippingCity>";
$request_xml .= "<ShippingState>".$shippingAddress->getState()."</ShippingState>";
$request_xml .= "<ShippingZip>".$shippingAddress->getPostcode()."</ShippingZip>";
$request_xml .= "<ShippingCountry>".$shippingAddress->getCountry()."</ShippingCountry>";
$request_xml .= "<ShippingEveningPhone>".$shippingAddress->getTelephone()."</ShippingEveningPhone>";
$request_xml .= "<GrandTotal>".$this->getGrandTotal()."</GrandTotal>";
$request_xml .= "<AVSCode>Z</AVSCode>";
$request_xml .= "<BIN>551409</BIN>";
 //Mage::log("7", null, 'efraud.log');
 //foreach($cards as $card):
     $request_xml .= "<CardType>".$cards['cc_type']."</CardType>";
     $request_xml .= "<CreditCard>".$cards['cc_last4']."</CreditCard>";
// endforeach;
$request_xml .= "<CIDResponse>M</CIDResponse>";
$request_xml .= "<DoNotRemove>true</DoNotRemove>";
$request_xml .= "<Custom1>".$this->getShippingAmount()."</Custom1>";
$request_xml .= "<products>";
$ordered_items = $this->getAllItems();
foreach($ordered_items as $item){ 
$sku = $item->getSku();
	$request_xml .= "<product>";
	$request_xml .= "<SKU>".substr($sku,0,49)."</SKU>";
	$request_xml .= "<ProductName>".$item->getName()."</ProductName>";
	$request_xml .= "<ProductSellingPrice>".$item->getPrice()."</ProductSellingPrice>";
	$request_xml .= "<ProductQty>".(int)$item->getQtyOrdered()."</ProductQty>";
	$request_xml .= "</product>";
}
 //Mage::log("10", null, 'efraud.log');
$request_xml .= "</products>";
$request_xml .= "</order></orders>";
return $request_xml;
        
    }
    
public function sendToFraud(){
$username = Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/username');
$password = Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/password');
$api = Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/api_url');

try {
	

$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, $api); 
curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
curl_setopt($ch, CURLOPT_POST, true );

curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getOrderXml()); 
$result = curl_exec($ch);
curl_close($ch); 
return $result;
}catch(Exception $e)
{
	Mage::log(print_r($e, true), null, 'efraud.log');
}
}
}
		