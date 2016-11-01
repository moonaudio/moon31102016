<?php

define("SHIPPINGZXCART_VERSION","3.0.7.8833");

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

//Function for checking Include Files
function Check_Include_File($filename)
{
	if(file_exists($filename))
	{
		return true;
	}
	else
	{
		echo "\"$filename\" is not accessible.";
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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZXCART_VERSION && SHIPPINGZXCART_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZXcart.php [".SHIPPINGZXCART_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}
################################################ Function convert_dim_unit #######################
//converts dim unit to desired units
function convert_dim_unit($from_unit)
{
	
	$from_unit=trim($from_unit);
	if($from_unit=="inches")
	$from_unit="in";
	
	if($from_unit=='cm' || $from_unit=='in')
	{
		
		$converted_unit=strtoupper($from_unit);
	}
	else 
	{
	
		$converted_unit="Unknown";
	}
	
	return $converted_unit;
}
#######################################################################################################
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################################ Check Xcart Vesion ########################################
if(!defined("XCART_VERSION_5"))
{
	define("XCART_VERSION_5","0");
}
#########################################################################################################################
if(XCART_VERSION_5)
{
/************************************************** Code block related to version 5 **********************************/ 
 
		//Check for xcart include files
		if(Check_Include_File("./top.inc.php"))
		require "./top.inc.php";
		
		//Get database connection details
		$sql_user=trim(\XLite::getInstance()->getOptions(array('database_details', 'username')));
		$sql_password=trim(\XLite::getInstance()->getOptions(array('database_details', 'password')));
		$sql_host=trim(\XLite::getInstance()->getOptions(array('database_details', 'hostspec')));
		$sql_db=trim(\XLite::getInstance()->getOptions(array('database_details', 'database')));
		$tablePrefix = trim(\XLite::getInstance()->getOptions(array('database_details', 'table_prefix')));
		
		
		if (extension_loaded('pdo') && extension_loaded('pdo_mysql') ) 
		{
			
			try
			{
				$db_pdo=new PDO("mysql:host=".$sql_host.";dbname=".$sql_db, $sql_user, $sql_password);
			}
			catch( Exception $e )
			{
				echo $e->getMessage();
				exit;
			}
			
		}
		else
		{
			echo "The PDO system appears disabled. Please check the php.ini setting and make sure following extensions are enabled.<br>
			extension=pdo.so;<br>
			extension=pdo_mysql.so;<br>
			PDO is required.";
			exit;
		
		}
		
		
		
		################################################ Function Get_Address_Values ##############################################
		//Get required address field values
		###########################################################################################################################
		function Get_Address_Values($address_id,$address_field_id)
		{
			global $tablePrefix,$db_pdo;
			
			$address_field_value="";
			$customer_address_value_query_raw = "SELECT * FROM ".$tablePrefix."address_field_value WHERE address_id=:address_id and address_field_id=:address_field_id";
			$data=array(':address_id' => $address_id, ':address_field_id' => $address_field_id);
			$xcart_customer_address_value_res = $db_pdo->prepare($customer_address_value_query_raw);
			$xcart_customer_address_value_res->execute($data);
			foreach ($xcart_customer_address_value_res as $row) 
			{
				$address_field_value=$row['value'];	
			}
			return $address_field_value;
		}
		################################################ Function Get_Shipping_Status_ID ##############################################
		//Get shipping status id based on status code
		###########################################################################################################################
		function Get_Shipping_Status_ID($code)
		{
			global $tablePrefix,$db_pdo;
			
			$shipping_status_id=4;
			$shipping_status_query_raw = "SELECT * FROM ".$tablePrefix."order_shipping_statuses WHERE code=:code";
			$data=array(':code' => $code);
			$shipping_status_query_res = $db_pdo->prepare($shipping_status_query_raw);
			$shipping_status_query_res->execute($data);
			foreach ($shipping_status_query_res as $row) 
			{
				$shipping_status_id=$row['id'];	
			}
			return $shipping_status_id;
		}
		################################################ Function Get_Payment_Status_Code ##############################################
		//Get payment status code based on status id
		###########################################################################################################################
		function Get_Payment_Status_Code($id)
		{
			global $tablePrefix,$db_pdo;
			
			$payment_status_code='P';
			$payment_status_query_raw = "SELECT * FROM ".$tablePrefix."order_payment_statuses WHERE id=:id";
			$data=array(':id' => $id);
			$payment_status_query_res = $db_pdo->prepare($payment_status_query_raw);
			$payment_status_query_res->execute($data);
			foreach ($payment_status_query_res as $row) 
			{
				$payment_status_code=$row['code'];	
			}
			return $payment_status_code;
		}
		############################################## Class ShippingZXcart ##########################################################
		class ShippingZXcart extends ShippingZGenericShoppingCart
		{
			
			//cart specific functions goes here
			############################################## Function Check_DB_Access #################################
			//Check Database access
			#######################################################################################################
			
			function Check_DB_Access()
			{
				global $tablePrefix,$db_pdo;
				//check if xcart database can be acessed or not
				$sql = "SHOW COLUMNS FROM ".$tablePrefix."orders";
				
				$result = $db_pdo->prepare($sql);
				$result->execute();
				if ($result->rowCount()) 
				{
					$this->display_msg=DB_SUCCESS_MSG;
					
				}
				else
				{
					$this->display_msg=DB_ERROR_MSG;
				}
				
			}
			
			############################################## Function GetOrderCountByDate #################################
			//Get order count
			#######################################################################################################
			function GetOrderCountByDate($datefrom,$dateto)
			{
				
				global $tablePrefix,$db_pdo;
				
				//Get order count based on data range
				$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
				$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
				
				$order_status_filter=$this->PrepareXcartOrderStatusFilter();
				
				$sql = "SELECT o.order_id FROM ".$tablePrefix."orders o, ".$tablePrefix."order_shipping_statuses oss WHERE o.shipping_status_id=oss.id and o.is_order=1  and ".$order_status_filter." (o.date between :datefrom and :dateto || o.lastRenewDate between :datefrom and :dateto)";
				
				$data=array(':datefrom' => $datefrom_timestamp, ':dateto' =>$dateto_timestamp);
				
				$result = $db_pdo->prepare($sql);
				$result->execute($data);
			
				return  $result->rowCount();
			
			}
			############################################## Function UpdateShippingInfo #################################
			//Update order status
			#######################################################################################################
			function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
			{
				global $tablePrefix,$db_pdo;
				
				$sql = "SELECT * FROM ".$tablePrefix."orders o, ".$tablePrefix."order_shipping_statuses oss WHERE o.shipping_status_id=oss.id and o.order_id=:orderid";
				$data=array(':orderid' => $OrderNumber);
				
				$result = $db_pdo->prepare($sql);
				$result->execute($data);
				
				//check if order number is valid
				if($result->rowCount()>0)
				{
					foreach ($result as $row)
				   {
						
						if($ShipDate!="")
							$shipped_on=$ShipDate;
						else
							$shipped_on=date("m/d/Y");
							
						
							
						if($Carrier!="")
						{
							$Carrier=" via ".$Carrier;
						}
						
						if($Service!="")
						{
							$Service=" [".$Service."]";
						}
						
						$TrackingNumberString="";
						$tracking_sql="";
						if($TrackingNumber!="")
						{
							$TrackingNumberString=", Tracking number $TrackingNumber";
							$tracking_sql=" ,tracking=:tracking";
						}
						
						
						
						$current_order_status=$row['code'];
						
									
						//prepare $comments (appending existing notes)
						$comments=$row['notes']."---Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
						
					}
					
					
					//update order table
					if(XCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE==1)
					{
						$change_order_status='D';
					}
					else
					{
						if($current_order_status=='N' )
							$change_order_status='P';
						else if($current_order_status=='P')
							$change_order_status='S';
						else if($current_order_status=='S')
							$change_order_status='D';
						else
							$change_order_status=$current_order_status;
							
						
					}
					$change_order_status_id=Get_Shipping_Status_ID($change_order_status);
					
					$sql_upd="update ".$tablePrefix."orders set notes=:comments ".$tracking_sql.",shipping_status_id=:shipping_status_id  where order_id=:orderid";
						
						
					if($TrackingNumber!="")
					{
						
						$data_upd=array(':comments' => $comments, ':orderid' => $OrderNumber,':tracking'=>$TrackingNumber, ':shipping_status_id' => $change_order_status_id);
					}
					else
					{
						$data_upd=array(':comments' => $comments, ':orderid' => $OrderNumber, ':shipping_status_id' => $change_order_status_id);
					}
						
					$result_upd = $db_pdo->prepare($sql_upd);
					$result_upd->execute($data_upd);		 
						
					$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
					
				   
				}
				else
				{
					//display error message
					$this->display_msg=str_replace("ENTERED_ORDERED_NUMBER","#$OrderNumber",INVAID_ORDER_NUMBER_ERROR_MSG);
					$this->SetXmlError(1,$this->display_msg);
				
				}
			  
			}
			############################################## Function Fetch_DB_Orders #################################
			//Perform Database query & fetch orders based on date range
			#######################################################################################################
			
			function Fetch_DB_Orders($datefrom,$dateto)
			{
				global $tablePrefix,$db_pdo;
						
				$order_status_filter=$this->PrepareXcartOrderStatusFilter();
				
				$search=$order_status_filter." (o.date between :datefrom and :dateto || o.lastRenewDate between :datefrom and :dateto)";
				
				$orders_query_raw = "SELECT * FROM ".$tablePrefix."orders o, ".$tablePrefix."order_shipping_statuses oss WHERE o.shipping_status_id=oss.id and o.is_order=1 and ".$search ." order by order_id DESC";
				
				
				$data=array(':datefrom' => $this->GetServerTimeLocal(false,$datefrom), ':dateto' =>$this->GetServerTimeLocal(false,$dateto));
				
				$xcart_orders_res = $db_pdo->prepare($orders_query_raw);
				$xcart_orders_res->execute($data);
						  
				
				$counter=0;
				foreach ($xcart_orders_res as $row) 
				{
							
					$customer_id=$this->GetFieldNumber($row,"profile_id");
					$currency_id=$this->GetFieldNumber($row,"currency_id");
					
					
					//Preape array of required address field ids 
					$customer_address_fields_query_raw = "SELECT * FROM ".$tablePrefix."address_field WHERE enabled=:enabled";
					$data=array(':enabled' => 1);
					$xcart_customer_address_fields_res = $db_pdo->prepare($customer_address_fields_query_raw);
					$xcart_customer_address_fields_res->execute($data);
					$address_fields_id_arr=array();
					foreach ($xcart_customer_address_fields_res as $row_customer_address_fields_res) 
					{
						
						
						if($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="firstname")
						$address_fields_id_arr['firstname']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						elseif($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="lastname")
						$address_fields_id_arr['lastname']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						elseif($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="state_id")
						$address_fields_id_arr['state_id']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						elseif($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="custom_state")
						$address_fields_id_arr['custom_state']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						elseif($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="street")
						$address_fields_id_arr['address1']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						elseif($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="city")
						$address_fields_id_arr['city']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						elseif($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="zipcode" || $this->GetFieldString($row_customer_address_fields_res,"serviceName")=="postcode")
						{
							$address_fields_id_arr['postalcode']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						}
						elseif($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="phone")
						$address_fields_id_arr['phone']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						elseif($this->GetFieldString($row_customer_address_fields_res,"serviceName")=="company")
						$address_fields_id_arr['company']=$this->GetFieldNumber($row_customer_address_fields_res,"id");
						
					}
					
					//Prepare xcart order array
					$this->xcart_orders[$counter]=new stdClass();
					$this->xcart_orders[$counter]->orderid=$this->GetFieldNumber($row,"order_id");
					$this->xcart_orders[$counter]->order_info['PkgLength']="";
					
					//Get shipping address id
					$customer_address_main_shipping_query_raw = "SELECT * FROM ".$tablePrefix."profile_addresses WHERE is_shipping=1 and profile_id=:customer_id";
					$data=array(':customer_id' => $customer_id);
					$xcart_customer_address_main_shipping_res = $db_pdo->prepare($customer_address_main_shipping_query_raw);
					$xcart_customer_address_main_shipping_res->execute($data);
					
					foreach ($xcart_customer_address_main_shipping_res as $row_customer_address_main_shipping_res) 
					{
						$shipping_address_id=$this->GetFieldNumber($row_customer_address_main_shipping_res,"address_id");
						$this->xcart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($row_customer_address_main_shipping_res,"country_code");
						
					}
					
					//Get billing address id
					$customer_address_main_billing_query_raw = "SELECT * FROM ".$tablePrefix."profile_addresses WHERE is_billing=1 and profile_id=:customer_id";
					$data=array(':customer_id' => $customer_id);
					$xcart_customer_address_main_billing_res = $db_pdo->prepare($customer_address_main_billing_query_raw);
					$xcart_customer_address_main_billing_res->execute($data);
					foreach ($xcart_customer_address_main_billing_res as $row_customer_address_main_billing_res) 
					{
						$billing_address_id=$this->GetFieldNumber($row_customer_address_main_billing_res,"address_id");
						$this->xcart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($row_customer_address_main_billing_res,"country_code");
					}
					
					
					//Shipping details
					$this->xcart_orders[$counter]->order_shipping["FirstName"]=Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"firstname"));
					$this->xcart_orders[$counter]->order_shipping["LastName"]=Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"lastname"));
					$this->xcart_orders[$counter]->order_shipping["Company"]=Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"company"));
					$this->xcart_orders[$counter]->order_shipping["Address1"]=Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"address1"));
					$this->xcart_orders[$counter]->order_shipping["Address2"]="";
					$this->xcart_orders[$counter]->order_shipping["City"]=Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"city"));
					
					 $this->xcart_orders[$counter]->order_shipping["State"]="";
					if(Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"state_id"))!="")
					{
						
						$customer_state_query_raw = "SELECT * FROM ".$tablePrefix."states WHERE state_id=:state_id";
						$data=array(':state_id' => Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"state_id")));
						$xcart_customer_state_res = $db_pdo->prepare($customer_state_query_raw);
						$xcart_customer_state_res->execute($data);
						foreach ($xcart_customer_state_res as $row_xcart_customer_state_res) 
						{
						  $this->xcart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($row_xcart_customer_state_res,"state");
						}
					}
					else if(Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"custome_state"))!="")
					{
						$this->xcart_orders[$counter]->order_shipping["State"]=Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"custome_state"));
					}
					
					
					$this->xcart_orders[$counter]->order_shipping["PostalCode"]=Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"postalcode"));
					$this->xcart_orders[$counter]->order_shipping["Phone"]=Get_Address_Values($shipping_address_id,$this->GetFieldNumber($address_fields_id_arr,"phone"));
					
					//Get Customer Email
					$customer_profile_query_raw = "SELECT * FROM ".$tablePrefix."profiles WHERE profile_id=:profile_id";
					$data=array(':profile_id' => $customer_id);
					$xcart_customer_profile_res = $db_pdo->prepare($customer_profile_query_raw);
					$xcart_customer_profile_res->execute($data);
					foreach ($xcart_customer_profile_res as $row_xcart_customer_profile_res) 
					{
					  $this->xcart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($row_xcart_customer_profile_res,"login");
					}
					
					
					//Billing details
					$this->xcart_orders[$counter]->order_billing["FirstName"]=Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"firstname"));
					$this->xcart_orders[$counter]->order_billing["LastName"]=Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"lastname"));
					$this->xcart_orders[$counter]->order_billing["Company"]=Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"company"));
					$this->xcart_orders[$counter]->order_billing["Address1"]=Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"address1"));
					$this->xcart_orders[$counter]->order_billing["Address2"]="";
					$this->xcart_orders[$counter]->order_billing["City"]=Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"city"));
					
					$this->xcart_orders[$counter]->order_billing["State"]="";
					if(Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"state_id"))!="")
					{
						
						$customer_state_query_raw = "SELECT * FROM ".$tablePrefix."states WHERE state_id=:state_id";
						$data=array(':state_id' => Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"state_id")));
						$xcart_customer_state_res = $db_pdo->prepare($customer_state_query_raw);
						$xcart_customer_state_res->execute($data);
						foreach ($xcart_customer_state_res as $row_xcart_customer_state_res) 
						{
						  $this->xcart_orders[$counter]->order_billing["State"]=$this->GetFieldString($row_xcart_customer_state_res,"state");
						}
					}
					else if(Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"custome_state"))!="")
					{
						$this->xcart_orders[$counter]->order_billing["State"]=Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"custome_state"));
					}
					
					
					$this->xcart_orders[$counter]->order_billing["PostalCode"]=Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"postalcode"));
					$this->xcart_orders[$counter]->order_billing["Phone"]=Get_Address_Values($billing_address_id,$this->GetFieldNumber($address_fields_id_arr,"phone"));
					
					//Order info
					$this->xcart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,$this->GetFieldNumber($row,"date"));
					$this->xcart_orders[$counter]->order_info["ItemsTotal"]=$this->GetFieldMoney($row,"subtotal");
					$this->xcart_orders[$counter]->order_info["Total"]=$this->GetFieldMoney($row,"total");
					
					$this->xcart_orders[$counter]->order_info["Comments"]=$this->GetFieldString($row,"notes"); 
					
					//Find out shipping charges
					$order_surcharges_query_raw = "SELECT * FROM ".$tablePrefix."order_surcharges WHERE code='SHIPPING' and order_id=:order_id";
					$data=array(':order_id' => $this->xcart_orders[$counter]->orderid);
					$xcart_order_surcharges_res = $db_pdo->prepare($order_surcharges_query_raw);
					$xcart_order_surcharges_res->execute($data);
					foreach ($xcart_order_surcharges_res as $row_xcart_order_surcharges_res) 
					{
					  $this->xcart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->GetFieldString($row_xcart_order_surcharges_res,"value");
					}
					
					//Find out tax
					$order_surcharges_tax_query_raw = "SELECT * FROM ".$tablePrefix."order_surcharges WHERE code='TAX' and order_id=:order_id";
					$data=array(':order_id' => $this->xcart_orders[$counter]->orderid);
					$xcart_order_surcharges_tax_res = $db_pdo->prepare($order_surcharges_tax_query_raw);
					$xcart_order_surcharges_tax_res->execute($data);
					foreach ($xcart_order_surcharges_tax_res as $row_xcart_order_surcharges_tax_res) 
					{
					  $this->xcart_orders[$counter]->order_info["ItemsTax"]=$this->GetFieldString($row_xcart_order_surcharges_tax_res,"value");
					}
							
					$this->xcart_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($row,"shipping_method_name");
						
					$this->xcart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($row,"orderNumber");
					
					//Payment type
					$this->xcart_orders[$counter]->order_info["PaymentType"]="";
					if($this->ConvertPaymentType($this->GetFieldString($row,"payment_method_name"))!="")
					$this->xcart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($row,"payment_method_name"));
					
					//Show Order status	
					if(Get_Payment_Status_Code($this->GetFieldString($row,"payment_status_id"))!="P")
						$this->xcart_orders[$counter]->order_info["PaymentStatus"]=2;
					else
						$this->xcart_orders[$counter]->order_info["PaymentStatus"]=0;
					
					if($this->GetFieldString($row,"code")=="S" || $this->GetFieldString($row,"code")=="D")
						$this->xcart_orders[$counter]->order_info["IsShipped"]=1;
					else
						$this->xcart_orders[$counter]->order_info["IsShipped"]=0;
					
					//Order Items
					$order_products_query_raw = "SELECT * FROM ".$tablePrefix."order_items oi,".$tablePrefix."products p  WHERE  oi.object_id=p.product_id and oi.order_id=:order_id";
					$data=array(':order_id' => $this->xcart_orders[$counter]->orderid);
					$xcart_order_products_res = $db_pdo->prepare($order_products_query_raw);
					$xcart_order_products_res->execute($data);
					$i=0;
					foreach ($xcart_order_products_res as $product_data_arr) 
					{
						//Product Attributes
						$order_products_attributes_query_raw = "SELECT * FROM ".$tablePrefix."order_item_attribute_values  WHERE  item_id=:item_id";
						$data=array(':item_id' => $this->GetFieldNumber($product_data_arr,"item_id"));
						$xcart_order_products_attributes_res = $db_pdo->prepare($order_products_attributes_query_raw);
						$xcart_order_products_attributes_res->execute($data);
						$attribute_string="";
						
						foreach ($xcart_order_products_attributes_res as $product_attributes) 
						{
							if($attribute_string!="")
							$attribute_string.=",";
							
							$attribute_string.=$this->GetFieldString($product_attributes,"name").":".$this->GetFieldString($product_attributes,"value");
						
						}
						if($attribute_string!="")
						$attribute_string=" (".$attribute_string.")";
						
						$this->xcart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_data_arr,"name").$attribute_string;
						$this->xcart_orders[$counter]->order_product[$i]["Price"]=$this->GetFieldMoney($product_data_arr,"price");
						$this->xcart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_data_arr,"amount");
						$this->xcart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->GetFieldNumber($product_data_arr,"subtotal"));	
						$this->xcart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_data_arr,"sku");
						$this->xcart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($product_data_arr,"weight")*$this->GetFieldNumber($product_data_arr,"amount");
						
						$xcart_dimension_unit="";
						$sql_get_dimension_unit= "select * from ".$tablePrefix."config where name=:dim_symbol";
						$res_dimension_unit = $db_pdo->prepare($sql_get_dimension_unit);
						$res_dimension_unit->execute(array(':dim_symbol' =>"dim_symbol"));
						foreach($res_dimension_unit as $row_dimension_unit)
						{
							$xcart_dimension_unit=$this->GetFieldString($row_dimension_unit,"value");
							
						}
						
						if($xcart_dimension_unit!="" && $this->GetFieldString($product_data_arr,"boxLength")!="" && $this->xcart_orders[$counter]->order_info['PkgLength']=="")
						{
							
							$this->xcart_orders[$counter]->order_info['PkgLength']=$this->GetFieldNumber($product_data_arr,"boxLength");
							$this->xcart_orders[$counter]->order_info['PkgWidth']=$this->GetFieldNumber($product_data_arr,"boxWidth");
							$this->xcart_orders[$counter]->order_info['PkgHeight']=$this->GetFieldNumber($product_data_arr,"boxHeight");
							
							$dim_unit=convert_dim_unit($xcart_dimension_unit);
						}							
						
						$i++;
					}
					
					
					$this->xcart_orders[$counter]->num_of_products=$i;
					if($dim_unit!="")
					$this->xcart_orders[$counter]->order_info["UnitsOfMeasureLinear"]=$dim_unit;
					
					$counter++;
				}	
				
				
			}
			
			################################### Function GetOrdersByDate($datefrom,$dateto) ######################
			//Get orders based on date range
			#######################################################################################################
			function GetOrdersByDate($datefrom,$dateto)
			{
					
					$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
					
		
					if (isset($this->xcart_orders))
						return $this->xcart_orders;
					else
									return array();  
					
			}
			################################################ Function PrepareOrderStatusString #######################
			//Prepare order status string based on settings
			#######################################################################################################
			function PrepareXcartOrderStatusFilter()
			{
					
					$order_status_filter="";
					
					if(XCART_RETRIEVE_ORDER_STATUS_1_QUEUED==1)//considers new orders
					{
						$order_status_filter="  oss.code='N'";
					
					}
					if(XCART_RETRIEVE_ORDER_STATUS_2_PROCESSED==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" oss.code='P' ";
						}
						else
						{
							$order_status_filter.=" OR oss.code='P' ";
						}
					
					}
					if(XCART_RETRIEVE_ORDER_STATUS_3_COMPLETE==1) //consider shipped or delivered orders
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" oss.code='D' OR oss.code='S' ";
						}
						else
						{
							$order_status_filter.=" OR oss.code='D' OR oss.code='S'";
						}
					
					}
					
					if($order_status_filter!="")
					$order_status_filter="( ".$order_status_filter." ) and";
					
					return $order_status_filter;
					
			}
				
			
		}
}
else
{
/**************************************************** Code block related version<5 **********************************/
		//Check for xcart include files
		if(Check_Include_File("./top.inc.php"))
		require "./top.inc.php";
		
		if(Check_Include_File("./init.php"))
		require "./init.php";
		
		
		
		if (extension_loaded('pdo') && extension_loaded('pdo_mysql') ) 
		{
			$sql_host_updated=str_replace(":MYSQL","",$sql_host);
			$sql_host_updated=str_replace(":MySQL","",$sql_host);
			
			if(strstr($sql_host_updated,":"))
			{
				$temp_host=split(":",$sql_host_updated);
				$sql_host_updated=$temp_host[0];
			}
			
			try
			{
				$db_pdo=new PDO("mysql:host=".$sql_host_updated.";dbname=".$sql_db, $sql_user, $sql_password);
			}
			catch( Exception $e )
			{
				echo $e->getMessage();
				exit;
			}
			
		}
		else
		{
			echo "The PDO system appears disabled. Please check the php.ini setting and make sure following extensions are enabled.<br>
			extension=pdo.so;<br>
			extension=pdo_mysql.so;<br>
			PDO is required.";
			exit;
		
		}	
		
		############################################## Class ShippingZXcart ######################################
		class ShippingZXcart extends ShippingZGenericShoppingCart
		{
			
			//cart specific functions goes here
			################################################ Function GetProductOptions #######################
			//Extract product options
			#######################################################################################################
			function GetProductOptions($opt_arr)
			{
				$opt_string="";
				if(is_array($opt_arr))
				{
					foreach($opt_arr as $key=>$arr)
					{
						if($opt_string=="")
						{
							$opt_string=$arr['class'].":".$arr['option_name'];
						}
						else
						{
							$opt_string.=",".$arr['class'].":".$arr['option_name'];
						}
					}
				}
				if($opt_string!="")
				$opt_string="(".$opt_string.")";
				
				return $opt_string;
			}	
			############################################## Function Check_DB_Access #################################
			//Check Database access
			#######################################################################################################
			
			function Check_DB_Access()
			{
				global $sql_tbl,$db_pdo;
				//check if xcart database can be acessed or not
				$sql = "SHOW COLUMNS FROM $sql_tbl[orders]";
				
				$result = $db_pdo->prepare($sql);
				$result->execute();
				if ($result->rowCount()) 
				{
					$this->display_msg=DB_SUCCESS_MSG;
					
				}
				else
				{
					$this->display_msg=DB_ERROR_MSG;
				}
				
			}
			
			############################################## Function GetOrderCountByDate #################################
			//Get order count
			#######################################################################################################
			function GetOrderCountByDate($datefrom,$dateto)
			{
				
				global $sql_tbl,$db_pdo;
				
				//Get order count based on data range
				$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
				$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
				
				$order_status_filter=$this->PrepareXcartOrderStatusFilter();
				
				$sql = "SELECT COUNT(*) as total_pending_orders FROM $sql_tbl[orders] WHERE ".$order_status_filter." date between :datefrom and :dateto ";
				
				$data=array(':datefrom' => $this->MakeSqlSafe($datefrom_timestamp), ':dateto' => $this->MakeSqlSafe($dateto_timestamp));
				
				$result = $db_pdo->prepare($sql);
				$result->execute($data);
			
				return  $result->rowCount();
			
			}
			############################################## Function UpdateShippingInfo #################################
			//Update order status
			#######################################################################################################
			function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
			{
				global $sql_tbl,$db_pdo;
				
				$sql = "SELECT * FROM $sql_tbl[orders] WHERE orderid=:orderid";
				$data=array(':orderid' => $this->MakeSqlSafe($OrderNumber,1));
				
				$result = $db_pdo->prepare($sql);
				$result->execute($data);
				
				//check if order number is valid
				if($result->rowCount()>0)
				{
					foreach ($result as $row)
				   {
						
						if($ShipDate!="")
							$shipped_on=$ShipDate;
						else
							$shipped_on=date("m/d/Y");
							
						$shipping_str="";	
						$shipping_sql="";
							
						if($Carrier!="")
						{
							$shipping_str=$Carrier;
							$Carrier=" via ".$Carrier;
						}
						
						if($Service!="")
						{
							$Service=" [".$Service."]";
							$shipping_str.=$Service;
						}
						
						$TrackingNumberString="";
						$tracking_sql="";
						if($TrackingNumber!="")
						{
							$TrackingNumberString=", Tracking number $TrackingNumber";
							$tracking_sql=" ,tracking=:tracking";
						}
						
						if($shipping_str!="")
						$shipping_sql=" ,shipping=:shipping";
						
						$current_order_status=$row['status'];
						
									
						//prepare $comments (appending existing notes)
						$comments=$row['notes']."---Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
						
					}
					
					
					//update order table
					if(XCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE==1)
					{
						$change_order_status='C';
					}
					else
					{
						if($current_order_status=='Q' || $current_order_status=='A')
							$change_order_status='P';
						else if($current_order_status=='P')
							$change_order_status='C';
						else
							$change_order_status=$current_order_status;
							
						
					}
					
						$sql_upd="update $sql_tbl[orders] set status=:status, notes=:comments ".$tracking_sql.$shipping_sql."  where orderid=:orderid";
						
						
						if($TrackingNumber!="" && $shipping_str!="")
						{
							$data_upd=array(':status'=>$change_order_status,':comments' => $this->MakeSqlSafe($comments), ':orderid' => $this->MakeSqlSafe($OrderNumber,1),':tracking'=> $this->MakeSqlSafe($TrackingNumber),':shipping'=>$shipping_str);
						
						}
						else if($TrackingNumber!="" && $shipping_str=="")
						{
						
							$data_upd=array(':status'=>'C',':comments' => $this->MakeSqlSafe($comments), ':orderid' => $this->MakeSqlSafe($OrderNumber,1),':tracking'=> $this->MakeSqlSafe($TrackingNumber));
						}
						else if($TrackingNumber=="" && $shipping_str!="")
						{
						
							$data_upd=array(':status'=>'C',':comments' => $this->MakeSqlSafe($comments), ':orderid' => $this->MakeSqlSafe($OrderNumber,1),':shipping'=>$shipping_str);
						}
						else
						{
							$data_upd=array(':status'=>'C',':comments' => $this->MakeSqlSafe($comments), ':orderid' => $this->MakeSqlSafe($OrderNumber,1));
						}
						
						$result_upd = $db_pdo->prepare($sql_upd);
						$result_upd->execute($data_upd);		 
						
					$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
					
				   
				}
				else
				{
					//display error message
					$this->display_msg=str_replace("ENTERED_ORDERED_NUMBER","#$OrderNumber",INVAID_ORDER_NUMBER_ERROR_MSG);
					$this->SetXmlError(1,$this->display_msg);
				
				}
			  
			}
			############################################## Function Fetch_DB_Orders #################################
			//Perform Database query & fetch orders based on date range
			#######################################################################################################
			
			function Fetch_DB_Orders($datefrom,$dateto)
			{
				global $sql_tbl,$db_pdo;
				require_once('./include/func/func.order.php');
				
				$order_status_filter=$this->PrepareXcartOrderStatusFilter();
				
				$search=$order_status_filter." date between :datefrom and :dateto";
				
				$orders_query_raw = "select orderid from $sql_tbl[orders] where ".$search ." order by orderid DESC";
				
				
				$data=array(':datefrom' => $this->MakeSqlSafe($this->GetServerTimeLocal(false,$datefrom)), ':dateto' =>$this->MakeSqlSafe($this->GetServerTimeLocal(false,$dateto)));
				
				$xcart_orders_res = $db_pdo->prepare($orders_query_raw);
				$xcart_orders_res->execute($data);
						  
						
				$counter=0;
				$dim_unit="";
				foreach ($xcart_orders_res as $row) 
				{
					//Get order details & customer details
					$xcart_orders_temp=func_select_order($this->GetFieldNumber($row,"orderid"));
					
					//prepare order array
					$this->xcart_orders[$counter]=new stdClass();
					$this->xcart_orders[$counter]->orderid=$this->GetFieldNumber($row,"orderid");
					$this->xcart_orders[$counter]->order_info['PkgLength']="";
					
					//Get order products
					$order_data_arr=func_order_data($this->GetFieldNumber($row,"orderid"));
					$product_data_arr=$this->GetFieldString($order_data_arr,"products");
					
					$this->xcart_orders[$counter]->num_of_products=count($product_data_arr);
					
					//shipping details
					if($this->GetFieldString($xcart_orders_temp,"s_firstname")!="" && $this->GetFieldString($xcart_orders_temp,"s_lastname")!="")
					{
						$this->xcart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($xcart_orders_temp,"s_firstname");
						$this->xcart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($xcart_orders_temp,"s_lastname");
					}
					else
					{
						$this->xcart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($xcart_orders_temp,"firstname");
						$this->xcart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($xcart_orders_temp,"lastname");
					
					}
					
					$this->xcart_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($xcart_orders_temp,"company");
					$this->xcart_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($xcart_orders_temp,"s_address");
					$this->xcart_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($xcart_orders_temp,"s_address_2");
					$this->xcart_orders[$counter]->order_shipping["City"]=$this->GetFieldString($xcart_orders_temp,"s_city");
					$this->xcart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($xcart_orders_temp,"s_state");
					$this->xcart_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($xcart_orders_temp,"s_zipcode");
					$this->xcart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($xcart_orders_temp,"s_country");
					
					$this->xcart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($xcart_orders_temp,"phone");
					if($this->xcart_orders[$counter]->order_shipping["Phone"]=="")
					$this->xcart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($xcart_orders_temp,"s_phone");
					
					$this->xcart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($xcart_orders_temp,"email");
					
					//billing details
					if($this->GetFieldString($xcart_orders_temp,"b_firstname")!="" && $this->GetFieldString($xcart_orders_temp,"b_lastname")!="")
					{
					
						$this->xcart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($xcart_orders_temp,"b_firstname");
						$this->xcart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($xcart_orders_temp,"b_lastname");
					}
					else
					{
						$this->xcart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($xcart_orders_temp,"firstname");
						$this->xcart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($xcart_orders_temp,"lastname");
					
					}
					
					$this->xcart_orders[$counter]->order_billing["Company"]=$this->GetFieldString($xcart_orders_temp,"company");
					$this->xcart_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($xcart_orders_temp,"b_address");
					$this->xcart_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($xcart_orders_temp,"b_address_2");
					$this->xcart_orders[$counter]->order_billing["City"]=$this->GetFieldString($xcart_orders_temp,"b_city");
					$this->xcart_orders[$counter]->order_billing["State"]=$this->GetFieldString($xcart_orders_temp,"b_state");
					$this->xcart_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($xcart_orders_temp,"b_zipcode");
					$this->xcart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($xcart_orders_temp,"b_country");
					
					$this->xcart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($xcart_orders_temp,"phone");
					if($this->xcart_orders[$counter]->order_billing["Phone"]=="")
					$this->xcart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($xcart_orders_temp,"b_phone");
					
					//order info
					$this->xcart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,$this->GetFieldString($xcart_orders_temp,"date"));
					$this->xcart_orders[$counter]->order_info["ItemsTotal"]=$this->GetFieldMoney($xcart_orders_temp,"display_subtotal");
					$this->xcart_orders[$counter]->order_info["Total"]=$this->GetFieldMoney($xcart_orders_temp,"total");
					$this->xcart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->GetFieldMoney($xcart_orders_temp,"display_shipping_cost");
					$this->xcart_orders[$counter]->order_info["ItemsTax"]=$this->GetFieldMoney($xcart_orders_temp,"tax");
					$this->xcart_orders[$counter]->order_info["Comments"]=$this->MakeXMLSafe($this->GetFieldString($xcart_orders_temp,"customer_notes")); 
					
					//Get shipping method
					$shippingid=$xcart_orders_temp['shippingid'];
					$shipping_query_raw = "select shipping from $sql_tbl[shipping] where shippingid=:shippingid";
					$xcart_shipping_res =$db_pdo->prepare($shipping_query_raw);
					$xcart_shipping_res->execute(array(':shippingid' => $shippingid));
					
					$this->xcart_orders[$counter]->order_info["ShipMethod"]="Not Available";
					foreach($xcart_shipping_res as $shipping_row)
					{
						if($shipping_row['shipping']!="")
						{
							$this->xcart_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($shipping_row,"shipping");
						}
					}
					$this->xcart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($xcart_orders_temp,"orderid");
					
					//get payment type
					$this->xcart_orders[$counter]->order_info["PaymentType"]="";
					$sql_get_payment_method= "select * from $sql_tbl[payment_methods] where paymentid=:paymentid";
					$res_payment_method = $db_pdo->prepare($sql_get_payment_method);
					$res_payment_method->execute(array(':paymentid' => $this->GetFieldNumber($xcart_orders_temp,"paymentid")));
					foreach($res_payment_method as $row_payment_method)
					{
						if($this->ConvertPaymentType($this->GetFieldString($row_payment_method,"payment_method"))!="")
						$this->xcart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($row_payment_method,"payment_method"));
					}
					
					if($this->GetFieldString($xcart_orders_temp,"status")!="Q" && $this->GetFieldString($xcart_orders_temp,"status")!="A")
						$this->xcart_orders[$counter]->order_info["PaymentStatus"]=2;
					else
						$this->xcart_orders[$counter]->order_info["PaymentStatus"]=0;
					
					//Show Order status	
					if($this->GetFieldString($xcart_orders_temp,"status")=="C")
						$this->xcart_orders[$counter]->order_info["IsShipped"]=1;
					else
						$this->xcart_orders[$counter]->order_info["IsShipped"]=0;
					
					for($i=0;$i<$this->xcart_orders[$counter]->num_of_products;$i++)
					{
						
						$this->xcart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_data_arr,"product",$i).$this->GetProductOptions($this->GetFieldString($product_data_arr,"product_options",$i));
						$this->xcart_orders[$counter]->order_product[$i]["Price"]=$this->GetFieldMoney($product_data_arr,"price",$i);
						$this->xcart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_data_arr,"amount",$i);
						$this->xcart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->GetFieldNumber($product_data_arr,"price",$i)*$this->GetFieldNumber($product_data_arr,"amount",$i));	
						
						$this->xcart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($product_data_arr,"weight",$i)*$this->GetFieldNumber($product_data_arr,"amount",$i);
						
						$xcart_dimension_unit="";
						$sql_get_dimension_unit= "select * from $sql_tbl[config] where name=:dimensions_symbol";
						$res_dimension_unit = $db_pdo->prepare($sql_get_dimension_unit);
						$res_dimension_unit->execute(array(':dimensions_symbol' =>"dimensions_symbol"));
						foreach($res_dimension_unit as $row_dimension_unit)
						{
							$xcart_dimension_unit=$this->GetFieldString($row_dimension_unit,"value");
							
						}
						
						if($xcart_dimension_unit!="" && $this->GetFieldString($product_data_arr,"length",$i)!="" && $this->xcart_orders[$counter]->order_info['PkgLength']=="")
						{
							
							$this->xcart_orders[$counter]->order_info['PkgLength']=$this->GetFieldNumber($product_data_arr,"length",$i);
							$this->xcart_orders[$counter]->order_info['PkgWidth']=$this->GetFieldNumber($product_data_arr,"width",$i);
							$this->xcart_orders[$counter]->order_info['PkgHeight']=$this->GetFieldNumber($product_data_arr,"height",$i);
							
							$dim_unit=convert_dim_unit($xcart_dimension_unit);
						}	
						
						$this->xcart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_data_arr,"productcode",$i);
						 
						
						 
					}
					if($dim_unit!="")
					$this->xcart_orders[$counter]->order_info["UnitsOfMeasureLinear"]=$dim_unit;
					
					$counter++;
				}	
				
				
			}
			
			################################### Function GetOrdersByDate($datefrom,$dateto) ######################
			//Get orders based on date range
			#######################################################################################################
			function GetOrdersByDate($datefrom,$dateto)
			{
					
					$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
					
		
					if (isset($this->xcart_orders))
						return $this->xcart_orders;
					else
									return array();  
					
			}
			################################################ Function PrepareOrderStatusString #######################
			//Prepare order status string based on settings
			#######################################################################################################
			function PrepareXcartOrderStatusFilter()
			{
					
					$order_status_filter="";
					
					if(XCART_RETRIEVE_ORDER_STATUS_1_QUEUED==1)//considers queued/pre-authorized orders
					{
						$order_status_filter="  status='Q' OR  status='A' ";
					
					}
					if(XCART_RETRIEVE_ORDER_STATUS_2_PROCESSED==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='P' ";
						}
						else
						{
							$order_status_filter.=" OR status='P' ";
						}
					
					}
					if(XCART_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=" status='C' ";
						}
						else
						{
							$order_status_filter.=" OR status='C'";
						}
					
					}
					
					if($order_status_filter!="")
					$order_status_filter="( ".$order_status_filter." ) and";
					return $order_status_filter;
					
			 }
				
			
		   }
}
######################################### End of class ShippingZXcart ###################################################

	// create object & perform tasks based on command

	$obj_shipping_xcart=new ShippingZXcart;
	$obj_shipping_xcart->ExecuteCommand();

?>