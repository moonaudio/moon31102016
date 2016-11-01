<?php

define("SHIPPINGZMAGENTO_VERSION","3.0.7.8833");

# ################################################################################
# 	
#  (c) 2010-2014 Z-Firm LLC, ALL RIGHTS RESERVED.
#
#  This file is protected by U.S. and international copyright laws. Technologies and techniques herein are
#  the proprietary methods of Z-Firm LLC. 
#         
#         IMPORTANT
#         =========
#         THIS FILE IS RESTRICTED FOR USE IN CONNECTION WITH SHIPRUSH, MY.SHIPRUSH AND OTHER SOFTWARE 
#         PRODUCTS OWNED BY Z-FIRM LLC.  UNLESS EXPRESSLY PERMITTED BY Z-FIRM, ANY USE IS STRICTLY PROHIBITED.
#
#         THIS FILE, AND ALL PARTS OF SHIPRUSH_SHOPPINGCART_INTEGRATION_KIT__SEE_README_FILE.ZIP AND 
#         THE MY.SHIPRUSH KIT, ARE GOVERNED BY THE MY.SHIPRUSH TERMS OF SERVICE & END USER LICENSE AGREEMENT.
#         
#         The ShipRush License Agreement can be read here: http://www.zfirm.com/SHIPRUSH-EULA
#         
#         If you do not agree with these terms, this file and related files must be deleted immediately.
#
#         Thank you for using ShipRush!
# 	
################################################################################

// { $Revision: #96 $ }
// { $File: //depot/main/shiprush/Current/WebApplications/ShoppingCartsIntegration/shiprush_new_module/ShippingZMagento.php $ }

//Function for checking Include Files
function Check_Include_File($filename, $mage_check=0)
{
	if(file_exists($filename))
	{
		return true;
	}
	else
	{
		if($mage_check)
		{
			echo "\"$filename\" is not accessible.<br><br>";
			echo "Please, place all ShippingZ PHP files which are listed below at root folder of your magento website.<br>";
			echo "-ShippingZMagento.php<br>";
			echo "-ShippingZSettings.php<br>";
			echo "-ShippingZClasses.php<br>";
			echo "-ShippingZMessages.php<br>";
		}
		else
		{
			echo "\"$filename\" is not accessible.";
		}
		exit;
	}

}
//Check for ShippingZ integration files
if(Check_Include_File("ShippingZSettings.php"))
include("ShippingZSettings.php");
if(Check_Include_File("ShippingZClasses.php"))
include("ShippingZClasses.php");
if(Check_Include_File("ShippingZMessages.php"))
include("ShippingZMessages.php");

// TEST all the files are all the same version
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZMAGENTO_VERSION && SHIPPINGZMAGENTO_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZMagento.php [".SHIPPINGZMAGENTO_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}

if(!defined("Magento_Store_Code_To_Service"))
define("Magento_Store_Code_To_Service","-ALL-");

if(!defined("Magento_Enterprise_Edition"))
define("Magento_Enterprise_Edition",0);

if(Check_Include_File("../app/Mage.php",1))
include("../app/Mage.php");

$app = Mage::app();
$magentoVersion = Mage::getVersion();

	
if(Magento_Store_Code_To_Service!='-ALL-')
{
	$allStores = array_keys(Mage::app()->getStores());
	
	foreach ($allStores as $_eachStoreId)
	{
		$store = Mage::app()->getStore($_eachStoreId);
				
		if($store->getCode()==Magento_Store_Code_To_Service) 
		{
			$selected_store_id=$_eachStoreId;
	    }
	
	}
}
else
$selected_store_id="";
############################################### Check & adjust "default_socket_timeout"#######################################
$timeout_value="";
$timeout_value=@ini_get("default_socket_timeout");
if($timeout_value!="" && $timeout_value<120)
@ini_set("default_socket_timeout",120);
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZMagento ##########################################
class ShippingZMagento extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	############################################## Function Check_DB_Access #################################
	//Check Database access(for magento everything will be done using mage, hence check access to order model)
	#######################################################################################################
	
	function Check_DB_Access()
	{
		
		try //See if we can access order model
		{
			$orders_data = Mage::getModel('sales/order')->getCollection();
			$this->display_msg=DB_SUCCESS_MSG;
		}
		catch(Exception $e)
		{
			
			$this->display_msg="Could not access order model";
			$this->SetXmlError(1,$this->display_msg);
			exit;
				
		}
			
	}
	
	############################################## Function UpdateDatefrom  #################################
	//if Day(DateFrom) = Day(DateTo) then set DateFrom to previous day
	#######################################################################################################
	function UpdateDatefrom($datefrom,$dateto)
	{
		
		$day_datefrom=substr($datefrom,0,10);
		$day_dateto=substr($dateto,0,10);
		
		$time_str_datefrom=substr($datefrom,10);
		
		if($day_datefrom==$day_dateto)
		{
			$updated_date_from=date("Y-m-d",strtotime("-1 day", strtotime($day_datefrom)));
			$updated_date_from=$updated_date_from.$time_str_datefrom;
			return $updated_date_from;
		}
		else
		{
			return $datefrom;
		}
		
	}
	
	############################################## Function SafeUnserialize  #################################
	//This will return false in case the passed string is not unserializeable
	#######################################################################################################
	function SafeUnserialize($serialized_string) 
	{
   		if (strpos($serialized_string, "\0") === false &&  is_string($serialized_string) ) {
			if (strpos($serialized_string, 'O:') === false) {
			  
				return @unserialize($serialized_string);
			} else if (!preg_match('/(^|;|{|})O:[0-9]+:"/', $serialized_string)) {
			   
				return @unserialize($serialized_string);
			}
		}
		return false;
	}
	############################################## Function GetProductOptions  #################################
	//Used to get product attributes and sku for product variations
	#######################################################################################################
	function GetProductOptions($option_arr,$code='')
	{
			
			if($code=="")
			{
				//get attribute details
				$formatted_option_variation_details="";
							
				if(isset($option_arr['attributes_info']) && is_array($option_arr['attributes_info']))
				{
					
					foreach($option_arr['attributes_info'] as $key=>$val)
					{
						
						if(is_array($val))
						{
							foreach($val as $key2=>$value2)
							{
								
								if($key2=="label")
								{
									$curr_label=$value2;
								}
								else if($key2=="value")
								{
									if($formatted_option_variation_details!="")
										$formatted_option_variation_details.=", ".$curr_label.":".$value2;
									else
										$formatted_option_variation_details=$curr_label.":".$value2;
								}
								
							}
						}
						
					}
					
					
					if($formatted_option_variation_details!="")
					{
						return " (".$formatted_option_variation_details.")";	
					}
					else
					{
					
						return '';
					}
					
				}
			}
			else
			{
					//get simple sku
					if(isset($option_arr[$code]))
						return "-".$option_arr[$code];
					else
						return '';
								
			}

		
	}
	############################################## Function GetProductOptionValuebyLabel  #################################
	//Used to get product attributes by label
	#######################################################################################################
	function GetProductOptionValuebyLabel($option_string,$label='')
	{
		
			$option_arr=$this->SafeUnserialize($option_string);
			
			//get attribute details
			$formatted_option_variation_details="";
						
			if(isset($option_arr['attributes_info']))
			{
				foreach($option_arr['attributes_info'] as $key=>$val)
				{
					$curr_label=0;
					
					foreach($val as $key2=>$value2)
					{
						if($key2=="label")
						{
							if($value2==$label)
							{
								$curr_label=1;
							}
						}
						else if($key2=="value" && $curr_label==1)
						{
							return $value2;
						}
						
					}
					
				}
			}
			
			
		
	}
	############################################## Function Check_Field #################################
	//Check & Return field value if available
	#######################################################################################################
	function Check_Field($obj,$field,$arg="")
	{
		if(is_object($obj))
		{
			if($arg!="")
			{
				if($arg!=0)
				{
					if(null !==($obj->{$field}($arg)))
					{
						
						return $obj->{$field}($arg);
					}
					else
					{
						return "";
					}
				}
				else
				{
					if(null !==($obj->{$field}()))
					{
						
						return $obj->{$field}();
					}
					else
					{
						return "";
					}
				}
			}
			else
			{
				
				if($obj->{$field}!="")
				{
					
					return $obj->{$field};
				}
				else
				{
					return "";
				}
			}
			
		}
		else
		{
			return "";
		}
		
	}
	############################################## Function GetOrderCountByDate #################################
	//Get order count
	#######################################################################################################
	function GetOrderCountByDate($datefrom,$dateto)
	{
		global $selected_store_id,$magentoVersion;
		
		$order_status_filter=$this->PrepareMagentoOrderStatusFilter();
		
		if(!StandardPerformanceTest)
		$datefrom=$this->UpdateDatefrom($datefrom,$dateto);
		
		$coreResource = Mage::getSingleton('core/resource')->getConnection('core_read');
			 
		$whereCond  = array(
			$coreResource->quoteInto('updated_at >?', $this->GetServerTimeLocalMagento($datefrom)),
		   $coreResource->quoteInto('updated_at<=?', $this->GetServerTimeLocalMagento($dateto))
		);
		
		$whereCondString=join(' AND ', $whereCond);
				
		if(Magento_Store_Code_To_Service!="-ALL-" && is_numeric($selected_store_id))
		{
				//count orders from specific store
				$orders_data = Mage::getModel('sales/order')->getCollection();
				
				if($magentoVersion>1.3 || Magento_Enterprise_Edition==1)
				{
					$orders_data->addAttributeToSelect("increment_id")->getSelect()->where("(".$order_status_filter." ".$whereCondString." AND store_id = $selected_store_id)");
				}
				else
				{
					$orders_data->addFieldToFilter($order_status_filter)->addAttributeToSelect("increment_id")->getSelect()->where("( ".$whereCondString." AND store_id = $selected_store_id)");
				}
		}
		else
		{
				//count orders from all stores
				$orders_data = Mage::getModel('sales/order')->getCollection();
				
				if($magentoVersion>1.3 || Magento_Enterprise_Edition==1)
				{
					$orders_data->addAttributeToSelect("increment_id")->getSelect()->where("(".$order_status_filter." ".$whereCondString.")");
				}
				else
				{
					$orders_data->addFieldToFilter($order_status_filter)->addAttributeToSelect("increment_id")->getSelect()->where($whereCondString);
				}
				
				
		
		
		}
		$total_count = $orders_data->count();
			
		return $total_count;
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		
		global $selected_store_id;
		
		if($ShipDate!="")
			$shipped_on=$ShipDate;
		else
			$shipped_on=date("m/d/Y");
		
		if($Carrier!="")
		{
			$SelectedCarrier=$Carrier;
			$Carrier=" via ".$Carrier;
						
		}
		else
		{
			$SelectedCarrier="ups";
		}
			
		if($Service!="")
			$ServiceString=" [".$Service."]";
		else
			$ServiceString="";
		
				
		if(Magento_SendsShippingEmail_AddComments==1)
			$send_email_include_comments=true;
		else
			$send_email_include_comments=false;
			
			
		
		//prepare $comments 
		$TrackingString="";
		if($TrackingNumber!="")
		$TrackingString=", Tracking number $TrackingNumber";
		
		
		$comments="Shipped on $shipped_on".$Carrier.$ServiceString.$TrackingString;
		
					
		$coreResource = Mage::getSingleton('core/resource')->getConnection('core_read');
			 
		$whereCond  = $coreResource->quoteInto('increment_id=?', $OrderNumber);
			
		
		
		if(is_numeric($selected_store_id))
		{
		 $orders_data = Mage::getModel('sales/order')->getCollection();
        	$orders_data->addAttributeToSelect("*")
            ->getSelect()
            ->where("(".$whereCond." and store_id='".$selected_store_id."')");
			 $orders_data->loadData();
		}
		else
		{
			 $orders_data = Mage::getModel('sales/order')->getCollection();
        	$orders_data->addAttributeToSelect("*")
            ->getSelect()
            ->where("(".$whereCond.")");
			 $orders_data->loadData();
		}
				
		foreach ($orders_data as $order) 
		{
			$current_order_status=$order->getStatus();
		 	$related_store_id=$order->store_id;
		}
		if(!isset($order)) // check if order exits in magento store
		{
			$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
			$this->SetXmlError(1,$this->display_msg);
		}
		
		if(MAGENTO_SHIPPED_STATUS_COMPLETE_ALL_SHIPPED_ORDERS==1)
		{
			$change_order_status="complete";
		}
		else
		{   
		    
			if(strtolower($current_order_status)=="pending")
				$change_order_status="processing";
			else if(strtolower($current_order_status)=="processing")
				$change_order_status="complete";
			else
			$change_order_status=$current_order_status;
		}
		
		if(Magento_StoreShippingInComments==1)
		{
			try
			{
				// add comment using sales_order.addComment method
				$order->addStatusToHistory($change_order_status, $comments);
				
				if(Magento_SendsBuyerEmail==1 && !strstr($_SERVER['HTTP_HOST'],'localhost'))
				$order->sendOrderUpdateEmail(true, $comments);
				
				$order->save();
			}
			catch( Exception $e )
			{
				//display error message
				$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
				$this->SetXmlError(1,$this->display_msg);
			}
	   }
	   else
	   {
	   		try
			{
				
				
				//check if shipment id exists
				$shipment = $order->getShipmentsCollection()->getFirstItem();
				$newShipmentId = $shipment->getIncrementId();
				
		
				
				if(!isset($newShipmentId))
				{
					//create new shipment
					 $shipment = $order->prepareShipment();
					 $shipment->register();
                     $shipment->addComment($comments, false);
				}
				
				#add tracking number
				if($Service=="")
				$Service="Shipping Tracking";
				
				 $track = Mage::getModel('sales/order_shipment_track')->setNumber($TrackingNumber);
        
				
				$track->setCarrierCode(strtolower($SelectedCarrier));
				$track->setTitle($SelectedCarrier." ".$Service);
				$shipment->addTrack($track);
				
				 $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();		
			
			     
				 #send shipment email
				if(Magento_SendsShippingEmail)
				{
				   $shipment->sendEmail(true, ($send_email_include_comments ? $comments : ''));		
				   $shipment->setEmailSent(true);
			 	   $shipment->save();
				}
			
			
				#force status change
				$order->addStatusToHistory($change_order_status, $coreResource->quote($comments));
				
				if(Magento_SendsBuyerEmail==1 && !strstr($_SERVER['HTTP_HOST'],'localhost'))
				$order->sendOrderUpdateEmail(true, $comments);
				
				$order->save();
				$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success")); 

			}
			catch( Exception $e )
			{
				
				//display error message
				$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
				$this->SetXmlError(1,$e->getMessage());
			}
	   
	   }
		
	}
	############################################## Function Fetch_DB_Orders #################################
	//Fetch orders based on date range using sales_order.list method
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		global $selected_store_id,$magentoVersion;
		
		$order_status_filter=$this->PrepareMagentoOrderStatusFilter();
		$pageCount=1000;
		
		if(!StandardPerformanceTest)
		$datefrom=$this->UpdateDatefrom($datefrom,$dateto);
		
		$coreResource = Mage::getSingleton('core/resource')->getConnection('core_read');
			 
		$whereCond  = array(
			$coreResource->quoteInto('updated_at >?', $this->GetServerTimeLocalMagento($datefrom)),
		   $coreResource->quoteInto('updated_at<=?', $this->GetServerTimeLocalMagento($dateto))
		);
		
		$whereCondString=join(' AND ', $whereCond);
		
		if(Magento_Store_Code_To_Service!="-ALL-" && is_numeric($selected_store_id))
		{	
			//fetch orders from specific store
			$orders_data = Mage::getModel('sales/order')->getCollection();
			
       		if($magentoVersion>1.3 || Magento_Enterprise_Edition==1)
			{
				$orders_data->addAttributeToSelect("*")
            ->getSelect()
             ->where("(".$order_status_filter." ".$whereCondString." AND store_id = $selected_store_id)");
			}
			else
			{
			
       		$orders_data->addFieldToFilter($order_status_filter)->addAttributeToSelect("*")
            ->getSelect()
             ->where("( ".$whereCondString." AND store_id = $selected_store_id)");
            }
            
              $orders_data->setPageSize($pageCount)
			   ->setCurPagE(1)
			   ->loadData();
		}
		else
		{
			//fetch all orders irrespective of store
			$orders_data = Mage::getModel('sales/order')->getCollection();
			
        	if($magentoVersion>1.3 || Magento_Enterprise_Edition==1)
			{
				$orders_data->addAttributeToSelect("*")
            ->getSelect()
            ->where("(".$order_status_filter." ".$whereCondString.")");
			
			}
			else
			{
        	$orders_data->addFieldToFilter($order_status_filter)->addAttributeToSelect("*")
            ->getSelect()
            ->where($whereCondString);
            }
            
               $orders_data->setPageSize($pageCount)
			   ->setCurPagE(1)
			   ->loadData();
		
		
		
		}
		
		
		$this->magento_orders=array();
		$counter=0;
		foreach ($orders_data as $order) 
		{
			
			$order_id=$order->getIncrementId();
			
				
			//prepare order array
			$this->magento_orders[$counter]=new stdClass();
			$this->magento_orders[$counter]->orderid=$order_id;
			
			
			if(MAGENTO_READ_INVOICES)
			{
			
					//Retrieve invoice numbers
					$invoice_str="";
					
					$invoices = $order->getInvoiceCollection();
					if(isset($invoices))
					{
														
						foreach($invoices as $invoice)
						{
							if($invoice_str!="")
							$invoice_str.=" ";
							
							$invoice_str.=$invoice->getIncrementId();
							
						}
						
						$invoice_str=substr($invoice_str,0,50); //consider upto 50 chars
						$invoice_str=trim($invoice_str);				
					}
					
			}
			
			//shipping details
			$ShippingAddress=$order->getShippingAddress();
					
			$this->magento_orders[$counter]->order_shipping["FirstName"]=$this->Check_Field($ShippingAddress,'firstname');
			$this->magento_orders[$counter]->order_shipping["LastName"]=$this->Check_Field($ShippingAddress,'lastname');
			$this->magento_orders[$counter]->order_shipping["Company"]=$this->Check_Field($ShippingAddress,'getCompany','0');
			$this->magento_orders[$counter]->order_shipping["Address1"]=$this->Check_Field($ShippingAddress,'getStreet','1');
			$this->magento_orders[$counter]->order_shipping["Address2"]=$this->Check_Field($ShippingAddress,'getStreet','2');
			$this->magento_orders[$counter]->order_shipping["City"]=$this->Check_Field($ShippingAddress,'getCity','0');
			$this->magento_orders[$counter]->order_shipping["State"]=$this->Check_Field($ShippingAddress,'getRegionCode','0');
			$this->magento_orders[$counter]->order_shipping["PostalCode"]=$this->Check_Field($ShippingAddress,'getPostcode','0');
			$this->magento_orders[$counter]->order_shipping["Country"]=$this->Check_Field($ShippingAddress,'getCountryId','0');
			$this->magento_orders[$counter]->order_shipping["Phone"]=$this->Check_Field($ShippingAddress,'getTelephone','0');
			
			$this->magento_orders[$counter]->order_shipping["EMail"]=$this->Check_Field($order,'getCustomerEmail','0');
			
			//billing details
			$BillingAddress = $order->getBillingAddress();
			
			$this->magento_orders[$counter]->order_billing["FirstName"]=$this->Check_Field($BillingAddress,'firstname');
			$this->magento_orders[$counter]->order_billing["LastName"]=$this->Check_Field($BillingAddress,'lastname');
			$this->magento_orders[$counter]->order_billing["Company"]=$this->Check_Field($BillingAddress,'getCompany','0');
			$this->magento_orders[$counter]->order_billing["Address1"]=$this->Check_Field($BillingAddress,'getStreet','1');
			$this->magento_orders[$counter]->order_billing["Address2"]=$this->Check_Field($BillingAddress,'getStreet','2');
			$this->magento_orders[$counter]->order_billing["City"]=$this->Check_Field($BillingAddress,'getCity','0');
			$this->magento_orders[$counter]->order_billing["State"]=$this->Check_Field($BillingAddress,'getRegionCode','0');
			$this->magento_orders[$counter]->order_billing["PostalCode"]=$this->Check_Field($BillingAddress,'getPostcode','0');
			$this->magento_orders[$counter]->order_billing["Country"]=$this->Check_Field($BillingAddress,'getCountryId','0');
			$this->magento_orders[$counter]->order_billing["Phone"]=$this->Check_Field($BillingAddress,'getTelephone','0');
			
			//order info
			$this->magento_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTCMagento($order->getCreatedAt());
			
			if(MAGENTO_READ_INVOICES)
			$this->magento_orders[$counter]->order_info["ExternalID"]=$invoice_str;
			
			$this->magento_orders[$counter]->order_info["CurrencyCode"]=strtoupper($order->getOrderCurrencyCode());
			$this->magento_orders[$counter]->order_info["ItemsTotal"]=number_format($order->getSubtotal(),2,'.','');
			$this->magento_orders[$counter]->order_info["Total"]=number_format($order->getGrandTotal(),2,'.','');
				
			if(MAGENTO_MULTI_CURRENCY_VIEW_AS_BASE_CURRENCY)
			{
				$this->magento_orders[$counter]->order_info["CurrencyCode"]=strtoupper($order->getBaseCurrencyCode());
				$this->magento_orders[$counter]->order_info["ItemsTotal"]=number_format($order->getBaseSubtotal(),2,'.','');
				$this->magento_orders[$counter]->order_info["Total"]=number_format($order->getBaseGrandTotal(),2,'.','');
			}
			
			if($order->getTaxAmount()!="")
			{
				
				$this->magento_orders[$counter]->order_info["ItemsTax"]=number_format($order->getTaxAmount(),2,'.','');
				if(MAGENTO_MULTI_CURRENCY_VIEW_AS_BASE_CURRENCY)
				{
					$this->magento_orders[$counter]->order_info["ItemsTax"]=number_format($order->getBaseTaxAmount(),2,'.','');
				}
			}
			else
			{
				$this->magento_orders[$counter]->order_info["ItemsTax"]=0.00;
			}
			$this->magento_orders[$counter]->order_info["OrderNumber"]=$order_id;
			
			//Get Payment details
			$payment=$order->getPayment();
			$payment_method=Mage::helper('payment')->getMethodInstance($payment->getMethod());
			
			if($this->Check_Field($payment_method,"getTitle","0"))
			{
				$this->magento_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($payment_method->getTitle());
			}
			else
			{
				$this->magento_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType("other");
			}
			
			$this->magento_orders[$counter]->order_info["ShippingChargesPaid"]=number_format($order->getShippingAmount(),2,'.','');
			
			if(MAGENTO_MULTI_CURRENCY_VIEW_AS_BASE_CURRENCY)
			{
				$this->magento_orders[$counter]->order_info["ShippingChargesPaid"]=number_format($order->getBaseShippingAmount(),2,'.','');
			}
			
			$this->magento_orders[$counter]->order_info["ShipMethod"]=$order->getShippingDescription();
			$this->magento_orders[$counter]->order_info["Comments"]="";			
			
			if($order->getStatus()!="pending")
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status
			if($order->getStatus()=="complete")
				$this->magento_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->magento_orders[$counter]->order_info["IsShipped"]=0;
				
			//show if cancelled
			if($order->getStatus()=="canceled")
				$this->magento_orders[$counter]->order_info["IsCancelled"]=1;
			else
				$this->magento_orders[$counter]->order_info["IsCancelled"]=0;
				
				
			 //handle closed order
			if($order->getStatus()=="closed")
			{
				$this->magento_orders[$counter]->order_info["IsCancelled"]=1;
				$this->magento_orders[$counter]->order_info["PaymentStatus"]=0;
				$this->magento_orders[$counter]->order_info["IsShipped"]=0;
			}
			
			//Order Level Gift Message
			if(Magento_RetrieveOrderGiftMessage==1)
			{
				$message = Mage::getModel('giftmessage/message');
				$gift_message_id = $order->getGiftMessageId();
				
				if(!is_null($gift_message_id)) 
				{
						$message->load((int)$gift_message_id);
						$this->magento_orders[$counter]->order_info["Comments"]=$this->GetGiftMessageText($message);
				}
			}
			
			
			
			//Get order products
			$actual_number_of_products=0;
			
			
			$order_items=$order->getAllItems();
			
			
			foreach ($order_items as $item)
			{
				if($item->getParentItemId()=="")
				{
									
				
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Name"]=$item->getName().$this->GetProductOptions($item->getProductOptions());
							
				
				 if (version_compare(Mage::getVersion(), '1.3.0', '>='))
				{
					$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getPrice();
				}
				else
				{
					if ($item->hasOriginalPrice())
					{
						$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getOriginalPrice();
					}
					elseif ($item->hasCustomPrice())
					{
						$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getCustomPrice();
					}
					
				}
				
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["ExternalID"]=$item->getSku().$this->GetProductOptions($item->getProductOptionByCode('simple_sku'));
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]=number_format($item->getQtyOrdered(),2,'.','');;
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Total"]=number_format(($this->magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]*$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]),2,'.','');
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Total_Product_Weight"]=number_format(($item->getWeight()*$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]),2,'.','');
				
				$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Notes"]="";
				
				//Product Level Gift Message
				if(Magento_RetrieveProductGiftMessage==1)
				{
					$gift_message_id = $item->getGiftMessageId();
					
					if(!is_null($gift_message_id)) 
					{
							$message->load((int)$gift_message_id);
							$this->magento_orders[$counter]->order_product[$actual_number_of_products]["Notes"]=$message->getData('message');
					}
				
				}
				
				$actual_number_of_products++;
				
				}
			}
			
			$this->magento_orders[$counter]->num_of_products=$actual_number_of_products;
			
			
			
			$counter++;
		}	
	
		
		
	}

	function GetGiftMessageText($message)
	{
           $result = "";
           if ($message->getData('sender')) $result = $result."From: ".$message->getData('sender')."\r\n";
           if ($message->getData('recipient')) $result = $result."To: ".$message->getData('recipient')."\r\n\r\n";
           $result = $result.$message->getData('message');
           return $result;
        }

	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			

			if (isset($this->magento_orders))
				return $this->magento_orders;
			else
               return array();  

			
	}
	  
	  #################################### Convert UTC time to Magento Format ################################################
	  /* Magento stores all times in UTC but not in ISO 8601 format.Hence, change "YYYY-MM-DDThh:mm:ssZ" to "YYYY-MM-DD hh:mm:ss"*/
	  #########################################################################################################################
	  function GetServerTimeLocalMagento($server_date_iso) 
	  {
			
			if(strpos($server_date_iso,"Z"))
			{
				$utc_fotmat_temp=str_replace("Z","",$server_date_iso);
				$server_date_utc=str_replace("T"," ",$utc_fotmat_temp);;//"T" & "Z" removed from UTC format(in ISO 8601)
				
			}
			return $server_date_utc;
	  }	
	   #################################### Convert Magento Format to UTC################################################
	  /* Magento stores all times in UTC but not in ISO 8601 format.Hence, format date to ISO 8601 i.e "YYYY-MM-DDThh:mm:ssZ" */
	  #########################################################################################################################
	  function ConvertServerTimeToUTCMagento($server_date_utc) 
	  {
		$utc_fotmat_temp=$server_date_utc."Z";
		$server_date_iso=str_replace(" ","T",$utc_fotmat_temp);;//"T" & "Z" removed from UTC format(in ISO 8601)
		return $server_date_iso;
	  }	

	#######################################################################################################
	############################################### Function PrepareOrderStatusString #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareMagentoOrderStatusFilter()
	{
			
			global $magentoVersion;
			
			
			if($magentoVersion>1.3 || Magento_Enterprise_Edition==1)
			{
					$order_status_filter="";
					
					if(MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
					{
						$order_status_filter=" status='pending' ";
					
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='processing'  ";
						}
						else
						{
							$order_status_filter.=" OR status='processing'  ";
						}
					
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='complete'  ";
						}
						else
						{
							$order_status_filter.=" OR status='complete'  ";
						}
					
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='closed'  ";
						}
						else
						{
							$order_status_filter.=" OR status='closed' ";
						}
					
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='canceled'  ";
						}
						else
						{
							$order_status_filter.=" OR status='canceled'  ";
						}
					
					}
					
					if($order_status_filter!="")
					$order_status_filter="( ".$order_status_filter." ) and";
					
			}
			else
			{
					$order_status_filter=array();
			
					if(MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
					{
						$filter_pending=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_PROCESSING);
						array_push($order_status_filter,$filter_pending);	
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
					{
						$filter_processing=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_PROCESSING);
						array_push($order_status_filter,$filter_processing);	
								
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
					{
						$filter_complete=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_COMPLETE);
						array_push($order_status_filter,$filter_complete);	
								
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED==1)
					{
						$filter_closed=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_CLOSED);
						array_push($order_status_filter,$filter_closed);	
									
					}
					if(MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
					{
						$filter_cancelled=array('attribute'=>'status','eq'=>Mage_Sales_Model_Order::STATE_CANCELED);
						array_push($order_status_filter,$filter_cancelled);	
									
					}
			
			}
			return $order_status_filter;
			
	}
	
	
}
######################################### End of class ShippingZMagento ###################################################
	
	//create object & perform tasks based on command
	$obj_shipping_magento=new ShippingZMagento;
	$obj_shipping_magento->ExecuteCommand();	

?>