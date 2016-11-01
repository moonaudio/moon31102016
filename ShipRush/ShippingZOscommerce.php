<?php

define("SHIPPINGZOSCOMMERCE_VERSION","3.0.7.8833");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZOSCOMMERCE_VERSION && SHIPPINGZOSCOMMERCE_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZOscommerce.php [".SHIPPINGZOSCOMMERCE_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


// Check for Oscommerce include files
if(Check_Include_File('includes/application_top.php'))
require('includes/application_top.php');

if(Check_Include_File(OSCOMMERCE_ADMIN_DIRECTORY.'/includes/classes/order.php'))
require(OSCOMMERCE_ADMIN_DIRECTORY.'/includes/classes/order.php');

$db_pdo=new PDO("mysql:host=".DB_SERVER.";dbname=".DB_DATABASE, DB_SERVER_USERNAME, DB_SERVER_PASSWORD);
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZOscommerce ######################################
class ShippingZOscommerce extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		
        global $db_pdo;
		//check if oscommerce database can be acessed or not
		
		$shipping = $db_pdo->prepare('SHOW COLUMNS FROM '.TABLE_ORDERS);
		$shipping->execute();
		
		if ($shipping->rowCount()) 
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
		global $db_pdo;
		
		$order_status_filter=$this->PrepareOscommerceOrderStatusFilter();
		
		//Get pending order count based on data range			
		$sql = "SELECT * FROM ".TABLE_ORDERS." WHERE ".$order_status_filter." (( DATE_FORMAT(last_modified,\"%Y-%m-%d %T\") between :datefrom and :dateto) OR ( DATE_FORMAT(date_purchased,\"%Y-%m-%d %T\") between :datefrom and :dateto))";
		
		$orders = $db_pdo->prepare($sql);
		
		$data=array(':datefrom' => $this->GetServerTimeLocal(true,$datefrom) , ':dateto' => $this->GetServerTimeLocal(true,$dateto));
		
		$orders->execute($data);
		
		return $orders->rowCount();
	
	}
	
	############################################## Function Fetch_DB_Orders #################################
	//Perform Database query & fetch orders based on date range
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		global $db_pdo;
		
		$order_status_filter=$this->PrepareOscommerceOrderStatusFilter();
		
		$search=$order_status_filter." (( DATE_FORMAT(last_modified,\"%Y-%m-%d %T\") between :datefrom and :dateto) OR ( DATE_FORMAT(date_purchased,\"%Y-%m-%d %T\") between :datefrom and :dateto))";

		
		
		$orders_query_raw = "select orders_id from ".TABLE_ORDERS." where ".$search ." order by orders_id DESC";
		
		
		$orders = $db_pdo->prepare($orders_query_raw);
		
		$data=array(':datefrom' => $this->GetServerTimeLocal(true,$datefrom) , ':dateto' => $this->GetServerTimeLocal(true,$dateto));
		
		$orders->execute($data);
		
		
		
		$counter=0;
		foreach ($orders as $oscommerce_orders_row) 
		{
			$oscommerce_orders_temp=new order($this->GetFieldNumber($oscommerce_orders_row,"orders_id"));
			//print_r($oscommerce_orders_temp);exit;
			//prepare order array
			$this->oscommerce_orders[$counter]=new stdClass(); 
			$this->oscommerce_orders[$counter]->orderid=$this->GetFieldNumber($oscommerce_orders_row,"orders_id");
			$this->oscommerce_orders[$counter]->num_of_products=count($this->GetClassProperty($oscommerce_orders_temp,"products"));
			
			//shipping details
			$this->oscommerce_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($oscommerce_orders_temp->delivery,"name");
			$this->oscommerce_orders[$counter]->order_shipping["LastName"]="";
			$this->oscommerce_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($oscommerce_orders_temp->delivery,"company");
			$this->oscommerce_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($oscommerce_orders_temp->delivery,"street_address");
			
			$this->oscommerce_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($oscommerce_orders_temp->delivery,"suburb");
			$this->oscommerce_orders[$counter]->order_shipping["City"]=$this->GetFieldString($oscommerce_orders_temp->delivery,"city");
			$this->oscommerce_orders[$counter]->order_shipping["State"]=$this->GetFieldString($oscommerce_orders_temp->delivery,"state");
			$this->oscommerce_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($oscommerce_orders_temp->delivery,"postcode");
			$this->oscommerce_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($oscommerce_orders_temp->delivery,"country");
			$this->oscommerce_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($oscommerce_orders_temp->customer,"telephone");
			$this->oscommerce_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($oscommerce_orders_temp->customer,"email_address");
			
			//billing details
			$this->oscommerce_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($oscommerce_orders_temp->billing,"name");
			$this->oscommerce_orders[$counter]->order_billing["LastName"]="";
			$this->oscommerce_orders[$counter]->order_billing["Company"]=$this->GetFieldString($oscommerce_orders_temp->billing,"company");
			$this->oscommerce_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($oscommerce_orders_temp->billing,"street_address");
			$this->oscommerce_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($oscommerce_orders_temp->billing,"suburb");
			$this->oscommerce_orders[$counter]->order_billing["City"]=$this->GetFieldString($oscommerce_orders_temp->billing,"city");
			$this->oscommerce_orders[$counter]->order_billing["State"]=$this->GetFieldString($oscommerce_orders_temp->billing,"state");
			$this->oscommerce_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($oscommerce_orders_temp->billing,"postcode");
			$this->oscommerce_orders[$counter]->order_billing["Country"]=$this->GetFieldString($oscommerce_orders_temp->billing,"country");
			$this->oscommerce_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($oscommerce_orders_temp->customer,"telephone");
			
			//order info
			$this->oscommerce_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,strtotime($this->GetFieldString($oscommerce_orders_temp->info,"date_purchased")));
			
			//get tax, shipping and order total
			$row_order_tax_res= $db_pdo->prepare("SELECT * FROM ".TABLE_ORDERS_TOTAL." WHERE orders_id=:orderid and class=:class");
			$row_order_tax_res->execute(array(':orderid'=>$this->oscommerce_orders[$counter]->orderid,':class' => "ot_tax"));
			foreach ($row_order_tax_res as $row_order_tax_details)
			{
				$this->oscommerce_orders[$counter]->order_info["ItemsTax"]=$this->FormatNumber($this->GetFieldNumber($row_order_tax_details,"value"));
			}
			
			$this->oscommerce_orders[$counter]->order_info["ShipMethod"]="";
			$res_order_total_shipping= $db_pdo->prepare("SELECT * FROM ".TABLE_ORDERS_TOTAL." WHERE orders_id=:orderid  and class=:class");
			$res_order_total_shipping->execute(array(':orderid'=>$this->oscommerce_orders[$counter]->orderid,':class' => "ot_shipping"));
			foreach ($res_order_total_shipping as $row_order_shipping_details)
			{
				$this->oscommerce_orders[$counter]->order_info["ShippingChargesPaid"]=$this->FormatNumber($this->GetFieldNumber($row_order_shipping_details,"value"));
				$this->oscommerce_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($row_order_shipping_details,"title");
			}
			
			$this->oscommerce_orders[$counter]->order_info["ShipMethod"]=str_replace(":","",$this->oscommerce_orders[$counter]->order_info["ShipMethod"]);
			
			$res_order_total = $db_pdo->prepare("SELECT * FROM ".TABLE_ORDERS_TOTAL." WHERE orders_id=:orderid  and class=:class");
			$res_order_total->execute(array(':orderid'=>$this->oscommerce_orders[$counter]->orderid,':class' => "ot_total"));
			foreach ($res_order_total as $row_order_total_details)
			{
				$this->oscommerce_orders[$counter]->order_info["Total"]=$this->FormatNumber($this->GetFieldNumber($row_order_total_details,"value"));
			}
			
			
			$this->oscommerce_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($oscommerce_orders_row,"orders_id");
			
			$this->oscommerce_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($oscommerce_orders_temp->info,"payment_method"));
			
			if($this->GetFieldNumber($oscommerce_orders_temp->info,"orders_status")!="1")
				$this->oscommerce_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->oscommerce_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status	
			if($this->GetFieldNumber($oscommerce_orders_temp->info,"orders_status")=="3")
				$this->oscommerce_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->oscommerce_orders[$counter]->order_info["IsShipped"]=0;
				
				
			//Cancelled Order status
			if(OSCOMMERCE_CANCELLED_ORDER_STATUS_ID!=0)
			{
				if($this->GetFieldNumber($oscommerce_orders_temp->info,"orders_status")==OSCOMMERCE_CANCELLED_ORDER_STATUS_ID)
				$this->oscommerce_orders[$counter]->order_info["IsCancelled"]=1;
			}
			
			//Get Customer Comments
			$res_order_details = $db_pdo->prepare("SELECT * FROM ".TABLE_ORDERS_STATUS_HISTORY." WHERE orders_id=:orderid order by orders_status_history_id");
			$res_order_details->execute(array(':orderid'=>$this->oscommerce_orders[$counter]->orderid));
			foreach ($res_order_details as $row_order_details)
			{
				$this->oscommerce_orders[$counter]->order_info["Comments"]=$this->GetFieldString($row_order_details,"comments");
			}
			
			//Get order products
			$items_cost=0;
			
			for($i=0;$i<count($oscommerce_orders_temp->products);$i++)
			{
				
				//Get product attributes
				$product_option_arr="";
				
				if(isset($oscommerce_orders_temp->products[$i]['attributes']))
				$product_option_arr=$oscommerce_orders_temp->products[$i]['attributes'];
								
				$attributes="";
				if(is_array($product_option_arr))
				{
					foreach ($product_option_arr as $option_arr_row) 
					{
						if($attributes!="")
						$attributes.=",";
						
						$attributes.=$this->GetFieldString($option_arr_row,"option").":".$this->GetFieldString($option_arr_row,"value");
					}
					
					$this->oscommerce_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($oscommerce_orders_temp->products,"name",$i)." (".$attributes.")";
					
				}
				else
				{
					$this->oscommerce_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($oscommerce_orders_temp->products,"name",$i);
				}
				
			   			
				$this->oscommerce_orders[$counter]->order_product[$i]["Price"]=$this->GetFieldMoney($oscommerce_orders_temp->products,"price",$i);
				$this->oscommerce_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($oscommerce_orders_temp->products,"model",$i);
				$this->oscommerce_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($oscommerce_orders_temp->products,"qty",$i);
				$this->oscommerce_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->GetFieldNumber($oscommerce_orders_temp->products,"price",$i)*$this->GetFieldNumber($oscommerce_orders_temp->products,"qty",$i));
				
				$items_cost=$items_cost+$this->oscommerce_orders[$counter]->order_product[$i]["Total"];
							
				//Get product weight & calculate total product weight 
				$res_product = $db_pdo->prepare("select * from " . TABLE_PRODUCTS . " p  where p.products_model = :products_model");
				$res_product->execute(array(':products_model'=>$this->GetFieldString($oscommerce_orders_temp->products,"model",$i) ));
				foreach ($res_product as $row)
				{
				$this->oscommerce_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($row,"products_weight")*$this->GetFieldNumber($oscommerce_orders_temp->products,"qty",$i);
				}
			}
			
			$this->oscommerce_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($items_cost,2);
			
			
			
			
			
			$counter++;
		}	
		
		
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			
			if (isset($this->oscommerce_orders))
				return $this->oscommerce_orders;
			else
                       		return array();  


			
	}
	
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		global $db_pdo;
			
		$sql = "SELECT * FROM ".TABLE_ORDERS." WHERE orders_id=:order_id";
		$result = $db_pdo->prepare($sql);
		$result->execute(array(':order_id' => $OrderNumber));
		
		//check if order number is valid
		if($result->rowCount()>0)
		{
		
			if($ShipDate!="")
				$shipped_on=$ShipDate;
			else
				$shipped_on=date("m/d/Y");
				
			if($Carrier!="")
			$Carrier=" via ".$Carrier;
			
			if($Service!="")
			$Service=" [".$Service."]";
			
			$TrackingNumberString="";
			if($TrackingNumber!="")
			$TrackingNumberString=", Tracking number $TrackingNumber";
			
			$order_row=tep_db_fetch_array($result);
			$current_order_status=$order_row['orders_status'];
			
			//prepare $comments & save it
			$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
			
			
			
			if(OSCOMMERCE_SHIPPED_STATUS_SET_TO_STATUS_3_DELIVERED==1)
			{
			
				$upate_order_history=$db_pdo->prepare("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
							  (orders_id, orders_status_id, date_added, customer_notified, comments)
							  values (:order_id, :order_status_id, :date_added, :notify, :comments)");
							  
				$upate_order_history->execute(array(':order_id' => $OrderNumber,':order_status_id'=>3,':date_added' => date('Y-m-d H:i:s'),':notify' =>0,':comments' => $comments));	
							  
				//update order status
				 $upate_order=$db_pdo->prepare(" update ".TABLE_ORDERS."  set orders_status=:order_status_id where orders_id=:order_id");
				 $upate_order->execute(array(':order_id' => $OrderNumber,':order_status_id'=>3));
			}
			else
			{
				  if($current_order_status==1)
					$change_order_status=2;
				else if($current_order_status==2)
					$change_order_status=3;
				else
					$change_order_status=$current_order_status;
					
				 
				 $upate_order_history=$db_pdo->prepare("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
							  (orders_id, orders_status_id, date_added, customer_notified, comments)
							  values (:order_id, :order_status_id, :date_added, :notify, :comments )");
					$upate_order_history->execute(array(':order_id' => $OrderNumber,':order_status_id'=>$change_order_status,':date_added' => date('Y-m-d H:i:s'),':notify' =>0,':comments' => $comments));			  
							  
				  $upate_order=$db_pdo->prepare(" update ".TABLE_ORDERS."  set orders_status=:order_status_id where orders_id=:order_id");
				  $upate_order->execute(array(':order_id' => $OrderNumber,':order_status_id'=>$change_order_status));
			}
			$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
		}
		else
		{
			//display error message
			$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
			$this->SetXmlError(1,$this->display_msg);
		
		}
	}
	################################################ Function PrepareOscommerceOrderStatusFilter #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareOscommerceOrderStatusFilter()
	{
			
			$order_status_filter="";
			
			if(OSCOMMERCE_RETRIEVE_ORDER_STATUS_1_PENDING==1)
			{
				$order_status_filter=" orders_status=1 ";
			
			}
			if(OSCOMMERCE_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" orders_status=2 ";
				}
				else
				{
					$order_status_filter.=" OR orders_status=2 ";
				}
			
			}
			if(OSCOMMERCE_RETRIEVE_ORDER_STATUS_3_DELIVERED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" orders_status=3 ";
				}
				else
				{
					$order_status_filter.=" OR orders_status=3 ";
				}
			
			}
			
			if(OSCOMMERCE_RETRIEVE_ORDER_STATUS_4_CANCELLED==1 && OSCOMMERCE_CANCELLED_ORDER_STATUS_ID!=0)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" orders_status=".OSCOMMERCE_CANCELLED_ORDER_STATUS_ID;
				}
				else
				{
					$order_status_filter.=" OR orders_status=".OSCOMMERCE_CANCELLED_ORDER_STATUS_ID;
				}
			}
			if(OSCOMMERCE_RETRIEVE_PAYPAL_ORDER_STATUS_ID!=0)
			{
			
				if($order_status_filter=="")
				{
					$order_status_filter.=" orders_status=".OSCOMMERCE_RETRIEVE_PAYPAL_ORDER_STATUS_ID;
				}
				else
				{
					$order_status_filter.=" OR orders_status=".OSCOMMERCE_RETRIEVE_PAYPAL_ORDER_STATUS_ID;
				}
			
			}
			
			if($order_status_filter!="")
			$order_status_filter="( ".$order_status_filter." ) and";
			return $order_status_filter;
			
	}
	
	
}
######################################### End of class ShippingZOscommerce ###################################################

	//create object & perform tasks based on command
	$obj_shipping_oscommerce=new ShippingZOscommerce;
	$obj_shipping_oscommerce->ExecuteCommand();	

?>