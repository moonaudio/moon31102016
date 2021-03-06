<?php
class Matech_Cardprober_Model_Sales_Order extends Mage_Sales_Model_Order
{
      const EFRAUD_PENDING    = 'efraud_pending';
      const EFRAUD_AWAITING_RESPONSE    = 'efraud_awaiting_response';
      const EFRAUD_REMOVED_FRON_QUEUE    = 'efraud_removed_from_queue';
      const EFRAUD_SCOREONLY    = 'efraud_scoreOnly';
      const EFRAUD_NOINSURED    = 'efraud_notinsured';
      const EFRAUD_ALLOWED    = 'efraud_allowed';
      const EFRAUD_REJECTED    = 'efraud_rejected';
      const EFRAUD_FRAUD    = 'efraud_fraud';
      const EFRAUD_FRAUD_MISSED    = 'efraud_fraud_missed';
      const EFRAUD_CANCELLED    = 'efraud_cancelled';
   
    public function saveFraudStatus($response){
        $statuslog = Mage::getModel('cardprober/cardprober');
        $statuslog->setData('order_id', $this->getIncrementId());
        if($response->code == 'Success'){
                   $statuslog->setData('status', 'Submitted');
             }
        $statuslog->setData('message', $response->message);
        $statuslog->setData('status_flag', '0');
        $statuslog->save();
    }  
      
    public function setefraudcardproberPending()
    {    
         //if ($this->canCancel()) {
		
            $efraudResponse = $this->sendToFraud();
            if(!$efraudResponse){
                $efraudResponse = "<result><code>Error</code><message>Invalid XML</message></result>";
            }
            $response = new SimpleXMLElement($efraudResponse);
			
             if($response->code == 'Success'){
				
                 $this->setState(self::EFRAUD_PENDING, true);
			
                 $this->saveFraudStatus($response);
			
				 $this->addStatusHistoryComment("eFraud Security ". $response->code.' : '.$response->message);
				 //Mage::log("eFraud Security ". $response->code.' : '.$response->message, null, 'efraudprint.log');
				
             }
			 return $response;
             
		
       // }

        // return $this;
    }
    
    
 public function getOrderXml(){
	
$billingAddress = $this->getBillingAddress();

$shippingAddress = $this->getShippingAddress();
 
$payment = $this->getPayment();
$cards = $this->getPayment()->debug();
//Mage::log(print_r($cards, true), null, 'braintree.log');
$request_xml = "<?xml version='1.0' encoding='utf-8'?>";
$request_xml .= "<orders><order><SiteName>".Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/sitename');
$request_xml .="</SiteName>";
$request_xml .= "<OrderDate>".date('Y-m-d',strtotime($this->getCreatedAt()))."</OrderDate>";
$request_xml .= "<OrderNumber>".$this->getIncrementId()."</OrderNumber>";
$request_xml .= "<IPAddress>".$this->getRemoteIp()."</IPAddress>";
$request_xml .= "<BillingFirstName>".trim($billingAddress->getFirstname())."</BillingFirstName>";
$request_xml .= "<BillingLastName>".trim($billingAddress->getLastname())."</BillingLastName>";
 
if($billingAddress->getEmail()){
    $billingemail = $billingAddress->getEmail();
    
}else{
    $billingemail = $this->getCustomerEmail();
            
}

// state code
if($billingAddress->getRegionId()){
$s_code = Mage::getModel('directory/region')->load($billingAddress->getRegionId());
$state_code = $s_code->getCode(); 
} else {
	$state_code = $billingAddress->getRegion();
}
// end of state code



//Mage::log(print_r($billingAddress, true), null, 'braintree12.log');
$billStreet = $billingAddress->getStreet();

$request_xml .= "<BillingAddress1>".$billStreet[0]."</BillingAddress1>";
if($billStreet[1]){
$request_xml .= "<BillingAddress1>".$billStreet[1]."</BillingAddress1>";
}
$request_xml .= "<BillingCity>".$billingAddress->getCity()."</BillingCity>";
$request_xml .= "<BillingState>".$state_code."</BillingState>";
$request_xml .= "<BillingZip>".$billingAddress->getPostcode()."</BillingZip>";
$request_xml .= "<BillingCountry>".$billingAddress->getCountry()."</BillingCountry>";
$request_xml .= "<BillingEveningPhone>".$billingAddress->getTelephone()."</BillingEveningPhone>";
$request_xml .= "<BillingEmail>".$billingemail."</BillingEmail>";
$request_xml .= "<ShippingMethod>".$this->getShippingDescription()."</ShippingMethod>";
$request_xml .= "<ShippingFirstName>".trim($shippingAddress->getFirstname())."</ShippingFirstName>";
$request_xml .= "<ShippingLastName>".trim($shippingAddress->getLastname())."</ShippingLastName>";
$shipStreet = $shippingAddress->getStreet();
// state code
if($shippingAddress->getRegionId()){
$s_code_s = Mage::getModel('directory/region')->load($shippingAddress->getRegionId());
$state_code_s = $s_code_s->getCode(); 
} else {
	$state_code_s = $shippingAddress->getRegion();
}
// end of
$request_xml .= "<ShippingAddress1>".$shipStreet[0]."</ShippingAddress1>";
if($shipStreet[1]){
$request_xml .= "<ShippingAddress2>".$shipStreet[1]."</ShippingAddress2>";
}
$request_xml .= "<ShippingCity>".$shippingAddress->getCity()."</ShippingCity>";
$request_xml .= "<ShippingState>".$state_code_s."</ShippingState>";
$request_xml .= "<ShippingZip>".$shippingAddress->getPostcode()."</ShippingZip>";
$request_xml .= "<ShippingCountry>".$shippingAddress->getCountry()."</ShippingCountry>";
$request_xml .= "<ShippingEveningPhone>".$shippingAddress->getTelephone()."</ShippingEveningPhone>";
$request_xml .= "<GrandTotal>".$this->getGrandTotal()."</GrandTotal>";
if($cards['additional_information']['avsStreetAddressResponseCode']){
//$request_xml .= "<AVSCode>".$cards['additional_information']['avsStreetAddressResponseCode']."</AVSCode>";
	$request_xml .= "<AVSCode>".$this->checkAvs(strlen($billingAddress->getPostcode()),$cards['additional_information'])."</AVSCode>";
}
if($cards['cc_last4']){
$request_xml .= "<BIN>".substr($cards['cc_last4'],0, 6)."</BIN>";
}

 //foreach($cards as $card):
     $request_xml .= "<CardType>".$cards['cc_type']."</CardType>";
     $request_xml .= "<CreditCard>".$cards['cc_last4']."</CreditCard>";
// endforeach;
if($cards['additional_information']['cvvResponseCode']){
$request_xml .= "<CIDResponse>".$cards['additional_information']['cvvResponseCode']."</CIDResponse>";
}
$request_xml .= "<DoNotRemove>true</DoNotRemove>";
$request_xml .= "<Custom1>".$this->getShippingAmount()."</Custom1>";
$request_xml .= "<products>";
$ordered_items = $this->getAllItems();
foreach($ordered_items as $item){ 
$sku = $item->getSku();
$name = preg_replace( '"', '&quot;',$item->getName());
$name = preg_replace( "'", '&apos;',$name);
$name = preg_replace( '<', '&lt;',$name);
$name = preg_replace( '>', '&gt;',$name);
$name = preg_replace( '&', '&amp;',$name);
	$request_xml .= "<product>";
	$request_xml .= "<SKU>".substr($sku,0,49)."</SKU>";
	$request_xml .= "<ProductName>".$name."</ProductName>";
	$request_xml .= "<ProductSellingPrice>".$item->getPrice()."</ProductSellingPrice>";
	$request_xml .= "<ProductQty>".(int)$item->getQtyOrdered()."</ProductQty>";
	$request_xml .= "</product>";
}

$request_xml .= "</products>";
$request_xml .= "</order></orders>";

//Mage::log(print_r($request_xml, true), null, 'braintree.log');
//print_r($request_xml);
//die("---");
//Mage::log($request_xml, null, 'xml.log');
return $request_xml;
        
    }
    
public function sendToFraud(){
$username = Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/username');
$password = Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/password');
$api = Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/api_url');
$sitename = Mage::getStoreConfig('efraud_cardprober_section/efraud_cardprober_group/sitename');
if(!$username or !$password or !$api or !$sitename){
    Mage::getSingleton("core/session")->addError("eFraud Security Error : Please check your credential in configuration"); 
//$this->_getSession()->addError($this->__("eFraud Security Error : Please check your credential in configuration"));
}
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
Mage::log(print_r($this->getOrderXml(), true), null, 'efraudexinent.log');
Mage::log(print_r($result, true), null, 'efraudexinent.log');
return $result;
}catch(Exception $e)
{
	Mage::log(print_r($e, true), null, 'efraud.log');
}
}

	private function checkAvs($postCodeLength, $additionalInfo){
		
		$address = $additionalInfo[avsPostalCodeResponseCode];
		$zip = $additionalInfo[avsStreetAddressResponseCode];
		
		if($address === "M" && $zip === "U"){
			return "B";
		}
		else if($address === "U" && $zip === "U"){
			return "I";
		}
		else if($address === "U" && $zip === "M"){
			return "P";
		}
		else if($address === "E" && $zip === "E"){
			return "R";
		}
		else if($address === "S" && $zip === "S"){
			return "S";
		}
		else if($postCodeLength === 5){
			if($address === "N"){
				if($zip === "N"){
					return "N";
				}
				else if($zip ==="M"){
					return "Z";
				}
			}
			else if($address ==="M" && $zip === "N"){
				return "A";
			}
			else if($address ==="M" && $zip === "M"){
				return "Y";
			}
		}
		else if($postCodeLength === 9){
			if($address === "N" && $zip === "M"){
				return "W";
			}
			else if($address === "M" && $zip === "M"){
				return "Z";
			}
		}
		else{
			return "U";
		}
	}
}
		