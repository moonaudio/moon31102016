<?php

define("SHIPPINGZCUBECART_VERSION","3.0.7.8833");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZCUBECART_VERSION && SHIPPINGZCUBECART_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZCubecart.php [".SHIPPINGZCUBECART_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


#########################################################################################################################

if(Check_Include_File('includes/global.inc.php'))
require ('includes/global.inc.php');

$db_pdo=new PDO("mysql:host=".$glob['dbhost'].";dbname=".$glob['dbdatabase'], $glob['dbusername'], $glob['dbpassword']);

############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");
################################################ Function ConvertToAcceptedUnit() #######################
//Converts weight values to desired unit
#######################################################################################################
		function ConvertToAcceptedUnit($weight,$from_unit)
		{
			
			$from_unit=trim($from_unit);
			
			if($from_unit=='oz' || $from_unit=='ozs')
			{
				
				$converted_weight=($weight*0.0625)."~"."LBS";
			}
			else if($from_unit=='g' || $from_unit=='gm' || $from_unit=='gms')
			{
			
				$converted_weight=($weight*0.001)."~"."KGS";
			}
			else if($from_unit=='kg' || $from_unit=='kgs')
			{
			
				$converted_weight=$weight."~"."KGS";
			}
			else if($from_unit=='lb' || $from_unit=='lbs')
			{
			
				$converted_weight=$weight."~"."LBS";
			}
			else 
			{
			
				$converted_weight=$weight."~Unknown";
			}
			return $converted_weight;
		}


############################################## Class ShippingZCubecart ######################################
class ShippingZCubecart extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check cubecart Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		global $db_pdo, $glob, $table_additional_prefix;
		
		//check if cubecart database can be acessed or not
		$shipping = $db_pdo->prepare('SHOW COLUMNS FROM `'.$glob['dbprefix'].'CubeCart_order_summary`');
		$shipping->execute();
		
    	if ($shipping->rowCount()) 
		{
			
			$table_additional_prefix='CubeCart';
			$this->display_msg=DB_SUCCESS_MSG;
			
		}
		else
		{
			$shipping = $db_pdo->prepare('SHOW COLUMNS FROM `'.$glob['dbprefix'].'cubecart_order_summary`');
			$shipping->execute();
			
			if ($shipping->rowCount()) 
			{
				$table_additional_prefix='cubecart';
				$this->display_msg=DB_SUCCESS_MSG;
				
			}
			else
			{
				$this->display_msg=DB_ERROR_MSG;
			}
		}
		
	}
	
	############################################## Function GetOrderCountByDate #################################
	//Get order count
	#######################################################################################################
	function GetOrderCountByDate($datefrom,$dateto)
	{
		
		global $db_pdo, $glob, $table_additional_prefix;
		
		
		//Get order count based on data range
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$order_status_filter=$this->PrepareCubecartOrderStatusFilter();
		
			
			
		$sql = "SELECT COUNT(*) as total_pending_orders FROM `".$glob['dbprefix'].$table_additional_prefix."_order_summary` a
		LEFT JOIN `".$glob['dbprefix'].$table_additional_prefix."_order_history` oh ON (oh.`cart_order_id` = a.`cart_order_id`)
		WHERE ".$order_status_filter."  (( a.order_date between :datefrom and :dateto) OR  (oh.updated between :datefrom  and :dateto)) AND oh.`history_id` = (SELECT MAX(`history_id`) FROM `".$glob['dbprefix'].$table_additional_prefix."_order_history` moh WHERE moh.`cart_order_id` = a.`cart_order_id`)";
		
		
		$orders = $db_pdo->prepare($sql);
		
		$data=array(':datefrom' => $this->GetServerTimeLocal(false,$datefrom), ':dateto' => $this->GetServerTimeLocal(false,$dateto));
		
		$orders->execute($data);
		
		foreach ($orders AS $order_result)
		{
			return $order_result['total_pending_orders'];
		}
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		
		global $db_pdo, $glob, $table_additional_prefix;
		
		global $db_pdo;
		
		
		$result = $db_pdo->prepare("SELECT a.cart_order_id,oh.status FROM `".$glob['dbprefix'].$table_additional_prefix."_order_summary` a
		LEFT JOIN `".$glob['dbprefix'].$table_additional_prefix."_order_history` oh ON (oh.`cart_order_id` = a.`cart_order_id`)
		WHERE a.cart_order_id=:order_id AND oh.`history_id` = (SELECT MAX(`history_id`) FROM `".$glob['dbprefix'].$table_additional_prefix."_order_history` moh WHERE moh.`cart_order_id` = a.`cart_order_id`) ");
		$result->execute(array(':order_id' => $OrderNumber));
		
		//check if order number is valid
		if($result->rowCount()>0)
		{
			
			if($ShipDate!="")
				$shipped_on=$ShipDate;
			else
				$shipped_on=date("m/d/Y");
			
			$tracking_url="";	
			if($Carrier!="")
			{
				if(defined(strtoupper($Carrier)."_URL"))
				{
					$temp_url=constant(strtoupper($Carrier)."_URL");
					$tracking_url=" <a href=\"".$temp_url."\" target=\"_blank\"> $TrackingNumber</a>";
					$tracking_url=str_replace("[TRACKING_NUMBER]",$TrackingNumber,$tracking_url);
				}
				$Carrier=" via ".$Carrier;
			}
			
			if($Service!="")
			$Service=" [".$Service."]";
			
			$TrackingNumberString="";
			if($TrackingNumber!="")
			{
				if($tracking_url!="")
				{
					$TrackingNumberString=", Tracking number $tracking_url";
				}
				else
				{
					$TrackingNumberString=", Tracking number $TrackingNumber";
				}
			}
			
			foreach ($result as $order_row)
			{
				$current_order_status=$order_row['status'];
			}
			
			
								
			//prepare $comments 
			$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
		
			if(CUBECART_SHIPPED_STATUS_SET_TO_STATUS_3_DELIVERED==1)
			{
				
				 $upate_order_history=$db_pdo->prepare("insert into " .$glob['dbprefix'].$table_additional_prefix."_order_history
						  (cart_order_id, status,updated)
						  values (:order_id, :order_status_id,:date_added)");
				 $upate_order_history->execute(array(':order_id' => $OrderNumber,':order_status_id'=>3,':date_added' => time()));	
				 
							  
				//update order status
				$upate_order=$db_pdo->prepare(" update `".$glob['dbprefix'].$table_additional_prefix."_order_summary` set status=:order_status_id,ship_tracking=:ship_tracking where cart_order_id=:order_id");
				$upate_order->execute(array(':order_id' => $OrderNumber,':order_status_id'=>3,':ship_tracking'=>$TrackingNumber));	  
			 } 
			 else
			 {
			 	
				if($current_order_status==1)
					$change_order_status=2;
				else if($current_order_status==2)
					$change_order_status=3;
				else
					$change_order_status=$current_order_status;
				
				 $upate_order_history=$db_pdo->prepare("insert into " .$glob['dbprefix'].$table_additional_prefix."_order_history
						  (cart_order_id, status,updated)
						  values (:order_id, :order_status_id,:date_added)");
				 $upate_order_history->execute(array(':order_id' => $OrderNumber,':order_status_id'=>$change_order_status,':date_added' => time()));
						  
				
					$upate_order=$db_pdo->prepare(" update `".$glob['dbprefix'].$table_additional_prefix."_order_summary` set status=:order_status_id,ship_tracking=:ship_tracking where cart_order_id=:order_id");
				$upate_order->execute(array(':order_id' => $OrderNumber,':order_status_id'=>$change_order_status,':order_status_id'=>$change_order_status,':ship_tracking'=>$TrackingNumber));
				 
			 
			 }
			//save admin notes
			$upate_order_history=$db_pdo->prepare("insert into " .$glob['dbprefix'].$table_additional_prefix."_order_notes
						  (admin_id, cart_order_id,time,content)
						  values (:admin_id,:order_id,:date_added,:notes)");
				 $upate_order_history->execute(array(':admin_id' => 1,':order_id' => $OrderNumber,':date_added' => date("Y-m-d H:i:s"),':notes'=>$comments));
			
			
			$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
		}
		else
		{
			//display error message
			$this->display_msg=INVAID_ORDER_NUMBER_ERROR_MSG;
			$this->SetXmlError(1,$this->display_msg);
		
		}	
		
	}
	############################################## Function Fetch_DB_Orders #################################
	//Perform Database query & fetch orders based on date range
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		
		global $db_pdo, $glob, $table_additional_prefix;
		
		$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
		
		$order_status_filter=$this->PrepareCubecartOrderStatusFilter();
		
		$sql =  "SELECT a.cart_order_id,oh.status FROM `".$glob['dbprefix'].$table_additional_prefix."_order_summary` a
		LEFT JOIN `".$glob['dbprefix'].$table_additional_prefix."_order_history` oh ON (oh.`cart_order_id` = a.`cart_order_id`)
		WHERE ".$order_status_filter."  (( a.order_date between :datefrom and :dateto) OR  (oh.updated between :datefrom  and :dateto)) AND oh.`history_id` = (SELECT MAX(`history_id`) FROM `".$glob['dbprefix'].$table_additional_prefix."_order_history` moh WHERE moh.`cart_order_id` = a.`cart_order_id`)";
		
		$result = $db_pdo->prepare($sql);
		
		$data=array(':datefrom' => $datefrom_timestamp, ':dateto' => $dateto_timestamp);
		
		$result->execute($data);
		
		$counter=0;
		foreach ($result as $order_result)
		{
		
			$order_id=$order_result['cart_order_id'];
			$id_order_state=$order_result['status'];
			
			$sql_order =  "SELECT * FROM `".$glob['dbprefix'].$table_additional_prefix."_order_summary` where cart_order_id=:order_id";
			$result_order = $db_pdo->prepare($sql_order);
		
			$data_order=array(':order_id' => $order_id);
			
			$result_order->execute($data_order);
			foreach ($result_order as $cubecart_orders_row)
			{
				
			//prepare order array
			
			$this->cubecart_orders[$counter]=new stdClass();
			$this->cubecart_orders[$counter]->orderid=$this->GetFieldNumber($cubecart_orders_row,"cart_order_id");
			
			 //Get weight cubecart weight unit
			$uom_weight_cart="";
			$sql_config_b =  "SELECT array FROM `".$glob['dbprefix'].$table_additional_prefix."_config` where name='config'";
			$result_config_b = $db_pdo->prepare($sql_config_b);
			
			$result_config_b->execute();
			foreach ($result_config_b as $cubecart_config_b_row)
			{
				$config_data_temp=explode(",",base64_decode($cubecart_config_b_row['array']));
				if(is_array($config_data_temp))
				{
					foreach($config_data_temp as $key=>$val)
					{
					
						if(strstr($val,"product_weight_unit"))
						{
						  $weight_unit_data=explode(":",$val);
						  $uom_weight_cart=$weight_unit_data[1];
						  $uom_weight_cart=str_replace(array("'", "\"", "&quot;"), "", htmlspecialchars($uom_weight_cart));
						  
						}
					}
				}
			}
					
			//shipping details
			$this->cubecart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($cubecart_orders_row,"first_name_d");
			$this->cubecart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($cubecart_orders_row,"last_name_d");
			$this->cubecart_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($cubecart_orders_row,"company_name_d");
			$this->cubecart_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($cubecart_orders_row,"line1_d");			
			$this->cubecart_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($cubecart_orders_row,"line2_d");
			$this->cubecart_orders[$counter]->order_shipping["City"]=$this->GetFieldString($cubecart_orders_row,"town_d");
			
			if(is_numeric($this->GetFieldString($cubecart_orders_row,"state_d")))
			{
				$sql_state_d =  "SELECT name as state_name FROM `".$glob['dbprefix'].$table_additional_prefix."_geo_zone` where id=:state_id";
				$result_state_d = $db_pdo->prepare($sql_state_d);
				$data_state_d=array(':state_id' =>$this->GetFieldString($cubecart_orders_row,"state_d"));
				$result_state_d->execute($data_state_d);
				foreach ($result_state_d as $cubecart_state_d_row)
				{
					$this->cubecart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($cubecart_state_d_row,"state_name");
				}
			}
			else
			$this->cubecart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($cubecart_orders_row,"state_d");
			
			
			$this->cubecart_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($cubecart_orders_row,"postcode_d");
			
			$sql_country =  "SELECT name as country_name FROM `".$glob['dbprefix'].$table_additional_prefix."_geo_country` where numcode=:country_id";
			$result_country = $db_pdo->prepare($sql_country);
			$data_country=array(':country_id' =>$this->GetFieldString($cubecart_orders_row,"country_d"));
			$result_country->execute($data_country);
			foreach ($result_country as $cubecart_country_d_row)
			{
				$this->cubecart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($cubecart_country_d_row,"country_name");
			}
						
			
			$this->cubecart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($cubecart_orders_row,"phone");
			$this->cubecart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($cubecart_orders_row,"email");
			
			//billing details
			$this->cubecart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($cubecart_orders_row,"first_name");
			$this->cubecart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($cubecart_orders_row,"last_name");
			$this->cubecart_orders[$counter]->order_billing["Company"]=$this->GetFieldString($cubecart_orders_row,"company_name");
			$this->cubecart_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($cubecart_orders_row,"line1");			
			$this->cubecart_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($cubecart_orders_row,"line2");
			$this->cubecart_orders[$counter]->order_billing["City"]=$this->GetFieldString($cubecart_orders_row,"town");
			
			if(is_numeric($this->GetFieldString($cubecart_orders_row,"state")))
			{
				$sql_state_b =  "SELECT name as state_name FROM `".$glob['dbprefix'].$table_additional_prefix."_geo_zone` where id=:state_id";
				$result_state_b = $db_pdo->prepare($sql_state_b);
				$data_state_b=array(':state_id' =>$this->GetFieldString($cubecart_orders_row,"state"));
				$result_state_b->execute($data_state_b);
				foreach ($result_state_b as $cubecart_state_b_row)
				{
					$this->cubecart_orders[$counter]->order_billing["State"]=$this->GetFieldString($cubecart_state_b_row,"state_name");
				}
			}
			else
			$this->cubecart_orders[$counter]->order_billing["State"]=$this->GetFieldString($cubecart_orders_row,"state");
			
			$this->cubecart_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($cubecart_orders_row,"postcode");
			
			$sql_country_b =  "SELECT name as country_name FROM `".$glob['dbprefix'].$table_additional_prefix."_geo_country` where numcode=:country_id";
			$result_country_b = $db_pdo->prepare($sql_country_b);
			$data_country_b=array(':country_id' =>$this->GetFieldString($cubecart_orders_row,"country"));
			$result_country_b->execute($data_country_b);
			foreach ($result_country_b as $cubecart_country_b_row)
			{
				$this->cubecart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($cubecart_country_b_row,"country_name");
			}
			
			$this->cubecart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($cubecart_orders_row,"phone");
			
			
			//order info
			$this->cubecart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,$this->GetFieldString($cubecart_orders_row,"order_date"));
			$this->cubecart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($cubecart_orders_row,"cart_order_id");
			
			//get payment method
			$this->cubecart_orders[$counter]->order_info["PaymentType"]="";
		
			if($this->GetFieldString($cubecart_orders_row,"gateway")!="")
			$this->cubecart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($cubecart_orders_row,"gateway"));
			
			//get shipping charges
			$this->cubecart_orders[$counter]->order_info["ShipMethod"]="";
						
			if($this->GetFieldString($cubecart_orders_row,"ship_method")!="")
			$this->cubecart_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($cubecart_orders_row,"ship_method");
				
			$this->cubecart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->GetFieldNumber($cubecart_orders_row,"shipping");
			
			
			
			//tax amount
			$this->cubecart_orders[$counter]->order_info["ItemsTax"]=$this->GetFieldString($cubecart_orders_row,"total_tax");
						
			if($this->GetFieldNumber($cubecart_orders_row,"status")!=1)
				$this->cubecart_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->cubecart_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status	
			if($this->GetFieldNumber($cubecart_orders_row,"status")==3)
				$this->cubecart_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->cubecart_orders[$counter]->order_info["IsShipped"]=0;
			
			
			//Cancelled Order status
			if(CUBECART_CANCELLED_ORDER_STATUS_ID!=0)
			{
				if($this->GetFieldNumber($cubecart_orders_row,"status")==CUBECART_CANCELLED_ORDER_STATUS_ID)
				$this->cubecart_orders[$counter]->order_info["IsCancelled"]=1;
			}
			
			
			//Get Customer Comments
			$this->cubecart_orders[$counter]->order_info["Comments"]=$this->GetFieldString($cubecart_orders_row,"customer_comments");
			//Get order products
			$items_cost=0;
				
			
			$sql_product = "SELECT coi.*,ci.product_weight FROM `".$glob['dbprefix'].$table_additional_prefix."_order_inventory` coi,`".$glob['dbprefix'].$table_additional_prefix."_inventory` ci where coi.cart_order_id=:order_id and coi.product_id=ci.product_id";
			
			$i=0;
			$result_product = $db_pdo->prepare($sql_product);
		
			$data_product=array(':order_id' => $order_id);
			
			$result_product->execute($data_product);
			
			foreach ($result_product as $product_row) 
			{
				
				$this->cubecart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_row,"name");
				$unit_price=$this->GetFieldNumber($product_row,"price");
				$this->cubecart_orders[$counter]->order_product[$i]["Price"]=$unit_price;
				$this->cubecart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_row,"product_code");
				$this->cubecart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_row,"quantity");
				
				$this->cubecart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($unit_price*$this->GetFieldNumber($product_row,"quantity"));
				
				$items_cost=$items_cost+$this->cubecart_orders[$counter]->order_product[$i]["Total"];
						
				
				$uom_weight=$uom_weight_cart;		
				if($this->GetFieldNumber($product_row,"product_weight")!="")
				{
					$product_weight=$this->GetFieldNumber($product_row,"product_weight");
					$total_weight_with_unit=ConvertToAcceptedUnit(($product_weight*$this->GetFieldNumber($product_row,"quantity")),strtolower($uom_weight_cart));
					$total_weight_with_unit=explode("~",$total_weight_with_unit);
					$this->cubecart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=number_format($total_weight_with_unit[0],2,'.','');
					$uom_weight=$total_weight_with_unit[1];
				}
				
				$option_arr=unserialize($this->GetFieldString($product_row,"product_options"));
				$attributes="";

							
				if(count($option_arr)>0 && is_array($option_arr))
				{
					foreach ($option_arr as $option_arr_row) 
					{
						if($attributes!="")
						$attributes.=",";
						
						$attributes.=$option_arr_row;
					}
				}
				
				$this->cubecart_orders[$counter]->order_product[$i]["Notes"]=$attributes;
				
				if($attributes!="")
				$this->cubecart_orders[$counter]->order_product[$i]["Name"]=$this->cubecart_orders[$counter]->order_product[$i]["Name"]." (".$attributes.")";
				
				$i++;
			}
			
			$this->cubecart_orders[$counter]->num_of_products=$i;
			
			$this->cubecart_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($items_cost-$this->cubecart_orders[$counter]->order_info["ItemsTax"]-$this->cubecart_orders[$counter]->order_info["ShippingChargesPaid"],2);
			$this->cubecart_orders[$counter]->order_info["UnitsOfMeasureWeight"]=$uom_weight;
			$this->cubecart_orders[$counter]->order_info["Total"]=$this->FormatNumber($this->GetFieldNumber($cubecart_orders_row,"total"));
			
			
			}
			
			$counter++;
			
		}
		
			
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	//Get orders based on date range
	#######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			

			if (isset($this->cubecart_orders))
				return $this->cubecart_orders;
			else
                       		return array();  
			
	}
	################################################ Function PrepareOrderStatusString #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareCubecartOrderStatusFilter()
	{
			
			$order_status_filter="";
		
			if(CUBECART_RETRIEVE_ORDER_STATUS_1_PENDING==1)//pending
			{
				$order_status_filter.="  a.status='1'    ";
							
			}
			if(CUBECART_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)//Preparation in progress
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" a.status='2'";
				}
				else
				{
					$order_status_filter.=" OR   a.status='2'";
				}
			
			}
			if(CUBECART_RETRIEVE_ORDER_STATUS_3_COMPLETED==1)//completed
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" a.status='3'";
				}
				else
				{
					$order_status_filter.=" OR a.status='3'";
				}
			
			}
			if(CUBECART_RETRIEVE_ORDER_STATUS_3_DECLINED==1)//declined
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" a.status='4'";
				}
				else
				{
					$order_status_filter.=" OR a.status='4'";
				}
			
			}
			if(CUBECART_RETRIEVE_ORDER_STATUS_3_FAILED_FRAUD_REVIEW==1)//failed
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" a.status='5'";
				}
				else
				{
					$order_status_filter.=" OR a.status='5'";
				}
			
			}
			
			
			if(CUBECART_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)//cancelled
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" a.status=".CUBECART_CANCELLED_ORDER_STATUS_ID;
				}
				else
				{
					$order_status_filter.=" OR a.status=".CUBECART_CANCELLED_ORDER_STATUS_ID;
				}
			
			}
			
			if($order_status_filter!="")
			$order_status_filter="( ".$order_status_filter." ) and";
			return $order_status_filter;
			
	}
	
	
}
######################################### End of class ShippingZCubecart ###################################################

	// create object & perform tasks based on command

	$obj_shipping_cubecart=new ShippingZCubecart;
	$obj_shipping_cubecart->ExecuteCommand();

?>