<?php

define("SHIPPINGZCRELOADED_VERSION","3.0.7.8833");

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

//Check here for ShippingZ integration files
if(Check_Include_File("ShippingZSettings.php"))
include("ShippingZSettings.php");
if(Check_Include_File("ShippingZClasses.php"))
include("ShippingZClasses.php");
if(Check_Include_File("ShippingZMessages.php"))
include("ShippingZMessages.php");

// TEST all the files are all the same version
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZCRELOADED_VERSION && SHIPPINGZCRELOADED_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZCreloaded.php [".SHIPPINGZCRELOADED_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


// Check for creloaded include files
if(Check_Include_File('includes/application_top.php'))
require('includes/application_top.php');

if(Check_Include_File(CRELOADED_ADMIN_DIRECTORY.'/includes/classes/order.php'))
require(CRELOADED_ADMIN_DIRECTORY.'/includes/classes/order.php');

############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
############################################## Class ShippingZCreloaded ######################################
class ShippingZCreloaded extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		
       	//check if creloaded database can be acessed or not
		$sql = "SHOW COLUMNS FROM ".TABLE_ORDERS;
		$result = tep_db_query($sql);
		
        if (tep_db_num_rows($result)) 
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
		$order_status_filter=$this->PrepareCreloadedOrderStatusFilter();
		
		//Get pending order count based on data range			
		$sql = "SELECT * FROM ".TABLE_ORDERS." WHERE ".$order_status_filter." (( DATE_FORMAT(date_purchased,\"%Y-%m-%d %T\") between '".$this->GetServerTimeLocal(true,$datefrom) ."' and '".$this->GetServerTimeLocal(true,$dateto)."') OR (DATE_FORMAT(last_modified,\"%Y-%m-%d %T\") between '".$this->GetServerTimeLocal(true,$datefrom) ."' and '".$this->GetServerTimeLocal(true,$dateto)."') )";
		$result = tep_db_query($sql);
		
		return tep_db_num_rows($result);
	
	}
	
	############################################## Function Fetch_DB_Orders #################################
	//Perform Database query & fetch orders based on date range
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		$order_status_filter=$this->PrepareCreloadedOrderStatusFilter();
		
		$search=$order_status_filter." (( DATE_FORMAT(date_purchased,\"%Y-%m-%d %T\") between '".$this->GetServerTimeLocal(true,$datefrom)."' and '".$this->GetServerTimeLocal(true,$dateto)."') OR (DATE_FORMAT(last_modified,\"%Y-%m-%d %T\") between '".$this->GetServerTimeLocal(true,$datefrom)."' and '".$this->GetServerTimeLocal(true,$dateto)."'))";

		$orders_query_raw = "select orders_id from ".TABLE_ORDERS." where ".$search ." order by orders_id DESC";
		
			  
		$creloaded_orders_res = tep_db_query($orders_query_raw);
		$counter=0;
		while ($creloaded_orders_row=tep_db_fetch_array($creloaded_orders_res)) 
		{
			$creloaded_orders_temp=new order($this->GetFieldNumber($creloaded_orders_row,"orders_id"));
			
			//prepare order array
			$this->creloaded_orders[$counter]=new stdClass();
			$this->creloaded_orders[$counter]->orderid=$this->GetFieldNumber($creloaded_orders_row,"orders_id");
			$this->creloaded_orders[$counter]->num_of_products=count($this->GetClassProperty($creloaded_orders_temp,"products"));
			
			//shipping details
			$this->creloaded_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($creloaded_orders_temp->delivery,"name");
			$this->creloaded_orders[$counter]->order_shipping["LastName"]="";
			$this->creloaded_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($creloaded_orders_temp->delivery,"company");
			$this->creloaded_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($creloaded_orders_temp->delivery,"street_address");
			$this->creloaded_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($creloaded_orders_temp->delivery,"suburb");
			$this->creloaded_orders[$counter]->order_shipping["City"]=$this->GetFieldString($creloaded_orders_temp->delivery,"city");
			$this->creloaded_orders[$counter]->order_shipping["State"]=$this->GetFieldString($creloaded_orders_temp->delivery,"state");
			$this->creloaded_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($creloaded_orders_temp->delivery,"postcode");
			$this->creloaded_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($creloaded_orders_temp->delivery,"country");
			$this->creloaded_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($creloaded_orders_temp->customer,"telephone");
			$this->creloaded_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($creloaded_orders_temp->customer,"email_address");
			
			//billing details
			$this->creloaded_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($creloaded_orders_temp->billing,"name");
			$this->creloaded_orders[$counter]->order_billing["LastName"]="";
			$this->creloaded_orders[$counter]->order_billing["Company"]=$this->GetFieldString($creloaded_orders_temp->billing,"company");
			$this->creloaded_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($creloaded_orders_temp->billing,"street_address");
			$this->creloaded_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($creloaded_orders_temp->billing,"suburb");
			$this->creloaded_orders[$counter]->order_billing["City"]=$this->GetFieldString($creloaded_orders_temp->billing,"city");
			$this->creloaded_orders[$counter]->order_billing["State"]=$this->GetFieldString($creloaded_orders_temp->billing,"state");
			$this->creloaded_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($creloaded_orders_temp->billing,"postcode");
			$this->creloaded_orders[$counter]->order_billing["Country"]=$this->GetFieldString($creloaded_orders_temp->billing,"country");
			$this->creloaded_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($creloaded_orders_temp->customer,"telephone");
			
			//order info
			$this->creloaded_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,strtotime($this->GetFieldString($creloaded_orders_temp->info,"date_purchased")));
			//echo ;exit;
		    $this->creloaded_orders[$counter]->order_info["ShippingChargesPaid"]=$this->GetFieldMoney($creloaded_orders_temp->info,"shipping_cost");
			$this->creloaded_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($creloaded_orders_temp->info,"shipping_method");
						
			$this->creloaded_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($creloaded_orders_row,"orders_id");
			$this->creloaded_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($creloaded_orders_temp->info,"payment_method"));
			
			if($this->GetFieldNumber($creloaded_orders_temp->info,"orders_status_number")!="1")
				$this->creloaded_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->creloaded_orders[$counter]->order_info["PaymentStatus"]=0;
				
			
			//Show Order status	
			if($this->GetFieldNumber($creloaded_orders_temp->info,"orders_status_number")=="3")
				$this->creloaded_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->creloaded_orders[$counter]->order_info["IsShipped"]=0;
			
			
				
			//Get Customer Comments
			$res_order_details = tep_db_query("SELECT * FROM ".TABLE_ORDERS_STATUS_HISTORY." WHERE orders_id=".$this->creloaded_orders[$counter]->orderid." order by orders_status_history_id");
			$row_order_details=tep_db_fetch_array($res_order_details);
			
			$this->creloaded_orders[$counter]->order_info["Comments"]=$this->MakeXMLSafe($this->GetFieldString($row_order_details,"comments"));
			
			//Get order products
			$items_cost=0;
			$items_tax=0;
			for($i=0;$i<count($creloaded_orders_temp->products);$i++)
			{
				
				$this->creloaded_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($creloaded_orders_temp->products,"name",$i);
				$this->creloaded_orders[$counter]->order_product[$i]["Price"]=$this->GetFieldMoney($creloaded_orders_temp->products,"price",$i);
				$this->creloaded_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($creloaded_orders_temp->products,"model",$i);
				$this->creloaded_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($creloaded_orders_temp->products,"qty",$i);
				$this->creloaded_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->GetFieldNumber($creloaded_orders_temp->products,"price",$i)*$this->GetFieldNumber($creloaded_orders_temp->products,"qty",$i));
				
				$items_cost=$items_cost+$this->creloaded_orders[$counter]->order_product[$i]["Total"];
				$items_tax=$items_tax+$this->GetFieldNumber($creloaded_orders_temp->products,"tax",$i);
				
				//Get product weight & calculate total product weight 
				$res_product = tep_db_query("select p.products_weight from " . TABLE_PRODUCTS . " p  where p.products_id = '" . $this->GetFieldNumber($creloaded_orders_temp->products,"id",$i) . "'");
				$row=tep_db_fetch_array($res_product);
				$this->creloaded_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($row,"products_weight")*$this->GetFieldNumber($creloaded_orders_temp->products,"qty",$i);
				
			}
			
			$this->creloaded_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($items_cost);
			$this->creloaded_orders[$counter]->order_info["ItemsTax"]=$this->FormatNumber($items_tax);
			$this->creloaded_orders[$counter]->order_info["Total"]=$this->FormatNumber($items_cost+$this->creloaded_orders[$counter]->order_info["ItemsTax"]+$this->creloaded_orders[$counter]->order_info["ShippingChargesPaid"]);
			$counter++;
		}	
		
		
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			
			if (isset($this->creloaded_orders))
				return $this->creloaded_orders;
			else
                       		return array();  

			
	}
	
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
			
		$sql = "SELECT * FROM ".TABLE_ORDERS." WHERE orders_id=".$this->MakeSqlSafe($OrderNumber,1);
		$result = tep_db_query($sql);
		
		
		//check if order number is valid
		if(tep_db_num_rows($result)>0)
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
			
			if(CRELOADED_SHIPPED_STATUS_SET_TO_STATUS_3_DELIVERED==1)
			{
			
				tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
							  (orders_id, orders_status_id, date_added, customer_notified, comments)
							  values ('" . (int)$this->MakeSqlSafe($OrderNumber,1) . "', '3', now(), '0', '" . $this->MakeSqlSafe($comments). "')");
							  
				//update order status
				tep_db_query(" update ".TABLE_ORDERS."  set orders_status='3' where orders_id='". (int)$OrderNumber ."'");
			}
			else
			{
				 
				 if($current_order_status==1)
					$change_order_status=2;
				else if($current_order_status==2)
					$change_order_status=3;
				else
					$change_order_status=$current_order_status;
				
				 
				 tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
							  (orders_id, orders_status_id, date_added, customer_notified, comments)
							  values ('" . (int)$this->MakeSqlSafe($OrderNumber,1) . "', '".$change_order_status."', now(), '0', '" . $this->MakeSqlSafe($comments). "')");
							  
				tep_db_query(" update ".TABLE_ORDERS."  set orders_status='".$change_order_status."' where orders_id='". (int)$this->MakeSqlSafe($OrderNumber,1) ."'");
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
	################################################ Function PrepareCreloadedOrderStatusFilter #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareCreloadedOrderStatusFilter()
	{
			
			$order_status_filter="";
			
			if(CRELOADED_RETRIEVE_ORDER_STATUS_1_PENDING==1)
			{
				$order_status_filter=" orders_status=1 ";
			
			}
			if(CRELOADED_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
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
			if(CRELOADED_RETRIEVE_ORDER_STATUS_3_DELIVERED==1)
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
			
			if($order_status_filter!="")
			$order_status_filter="( ".$order_status_filter." ) and";
			return $order_status_filter;
			
	}
	
	
}
######################################### End of class ShippingZCreloaded ###################################################

	//create object & perform tasks based on command
	$obj_shipping_creloaded=new ShippingZCreloaded;
	$obj_shipping_creloaded->ExecuteCommand();	

?>