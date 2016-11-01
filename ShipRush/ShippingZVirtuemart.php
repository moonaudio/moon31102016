<?php

define("SHIPPINGZVIRTUEMART_VERSION","3.0.7.8833");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZVIRTUEMART_VERSION && SHIPPINGZVIRTUEMART_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZVirtuemart.php [".SHIPPINGZVIRTUEMART_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


#########################################################################################################################
  
define( '_JEXEC', 1 );
define( '_VALID_MOS', 1 );
define( 'JPATH_BASE', realpath(dirname(__FILE__)));
define( 'DS', DIRECTORY_SEPARATOR );
require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );
require_once ( JPATH_BASE .DS.'libraries'.DS.'joomla'.DS.'factory.php' );

$mainframe =JFactory::getApplication('site');
$mainframe->initialise();
$db = JFactory::getDbo();

if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'helpers'.DS.'config.php');
VmConfig::loadConfig();
define('USE_SQL_CALC_FOUND_ROWS' , true);
	
vmTrace('Called by',TRUE);

if (!class_exists('vmPSPlugin'))
require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

if (!class_exists('VirtueMartModelOrders')) {
require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
}
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");

############################################## Class ShippingZVirtuemart ######################################
class ShippingZVirtuemart extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	############################################## Function Check_Field #################################
	//Check & Return field value if available 
	#######################################################################################################
	function Check_Field($obj,$field)
	{
		
		if(is_object($obj))
		{
			
			if(null !==($obj->{$field}))
			{
						
				return $obj->{$field};
			}
			else
		   {
				return "";
			}
						
		}
		else
		{
			if(is_array($obj))
			{
				if(isset($obj[$field]))
				{
					return $obj[$field];
				}
				else
				{
					return "";
				}
			
			}
			else
			return "";
		}
		
	}
	############################################## Function Check_DB_Access #################################
	//Check Database access
	#######################################################################################################
	
	function Check_DB_Access()
	{
		global $db;
		//check if virtuemart database can be acessed or not
		$query = "SHOW COLUMNS FROM #__virtuemart_orders";
		
		$db->setQuery($query);
		$db->execute();
		$num_rows = $db->getNumRows();
		
        if ($num_rows>0) 
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
		
		global $db;
		
		//Get order count based on data range
		$datefrom_timestamp=$this->GetServerTimeLocal(true,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(true,$dateto);
		
		$order_status_filter=$this->PrepareVirtuemartOrderStatusFilter();
		
		
		$query = $db->getQuery(true);
		
		$query
    	->select($db->quoteName(array('virtuemart_order_id')))
    	->from($db->quoteName('#__virtuemart_orders'))
    	->where($order_status_filter." modified_on between ".$db->quote($datefrom_timestamp)." and ".$db->quote($dateto_timestamp));
		$db->setQuery($query);
		$db->execute();
		return  $db->getNumRows();
	
	}
	############################################## Function UpdateShippingInfo #################################
	//Update order status
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		global $db;
		
		
		$query = $db->getQuery(true);
		
		$query
    	->select($db->quoteName(array('order_status')))
    	->from($db->quoteName('#__virtuemart_orders'))
    	->where("virtuemart_order_id=".$db->quote($OrderNumber));
		$db->setQuery($query);
		$db->execute();
		
		//check if order number is valid
		if($db->getNumRows()>0)
		{
			$virtuemart_orders_res = $db->loadRowList();		  
			foreach ($virtuemart_orders_res as $row) 
			{	
				if($ShipDate!="")
					$shipped_on=$ShipDate;
				else
					$shipped_on=date("m/d/Y");
					
				$shipping_str="";	
								
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
				if($TrackingNumber!="")
				{
					$TrackingNumberString=", Tracking number $TrackingNumber";
				}
				
				
				
				$current_order_status=$row[0];
				
							
				$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
				
			}
			
			
			//update order table
			if(VIRTUEMART_SHIPPED_STATUS_SET_TO_STATUS_4_SHIPPED==1)
			{
			 	$change_order_status='S';
			}
			else
			{
				if($current_order_status=='P' )
					$change_order_status='U';
				else if($current_order_status=='U')
					$change_order_status='C';
				else if($current_order_status=='C')
					$change_order_status='S';
				else
					$change_order_status=$current_order_status;
					
				
			}
			
			//Change order status
			$query_status = $db->getQuery(true);
			
			$fields = array(
				$db->quoteName('order_status') . ' = ' . $db->quote($change_order_status),
				$db->quoteName('modified_on') . ' =  '. $db->quote(date("Y-m-d H:i:s"))
			);
 
			$conditions = array(
				$db->quoteName('virtuemart_order_id') . ' = ' . $db->quote($OrderNumber)
			);
 
			$query_status->update($db->quoteName('#__virtuemart_orders'))->set($fields)->where($conditions);
			$db->setQuery($query_status);
			$db->execute();
     		
          
	
			//Insert into order history table
			$query_history = $db->getQuery(true);
									
			$columns_history = array('virtuemart_order_id', 'order_status_code','customer_notified', 'comments', 'published','created_on', 'modified_on');
 			
			// Insert values.
			$values_history = array($db->quote($OrderNumber), $db->quote($change_order_status),0,$db->quote($comments), 1,$db->quote(date("Y-m-d H:i:s")),$db->quote(date("Y-m-d H:i:s")));
			 
		
			$query_history
				->insert($db->quoteName('#__virtuemart_order_histories'))
				->columns($db->quoteName($columns_history))
				->values(implode(',', $values_history));
 
			
			$db->setQuery($query_history);
			$db->execute();		 
			
	
			 //Change order status related to order items
			$query_item_status = $db->getQuery(true);
			
			$fields_items = array(
				$db->quoteName('order_status') . ' = ' . $db->quote($change_order_status),
				$db->quoteName('modified_on') . ' =  '. $db->quote(date("Y-m-d H:i:s"))
			);
 			
			$conditions_items = array(
				$db->quoteName('virtuemart_order_id') . ' = ' . $db->quote($OrderNumber)
			);
 
			$query_item_status->update($db->quoteName('#__virtuemart_order_items'))->set($fields_items)->where($conditions_items);
			$db->setQuery($query_item_status);
			$db->execute();
			
			
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
		global $db;
				
		$datefrom_timestamp=$this->GetServerTimeLocal(true,$datefrom);
		$dateto_timestamp=$this->GetServerTimeLocal(true,$dateto);
		
		$order_status_filter=$this->PrepareVirtuemartOrderStatusFilter();
		
		$query = $db->getQuery(true);
		
		$query
    	->select($db->quoteName(array('virtuemart_order_id')))
    	->from($db->quoteName('#__virtuemart_orders'))
    	->where($order_status_filter." modified_on between ".$db->quote($datefrom_timestamp)." and ".$db->quote($dateto_timestamp));
		$db->setQuery($query);
		$db->execute();
		$virtuemart_orders_res = $db->loadRowList();		  
		
		$counter=0;
		foreach ($virtuemart_orders_res as $row) 
		{
			
			//Get order details & customer details
					
			$virtuemart_orders_temp=VirtueMartModelOrders::getOrder($this->GetFieldNumber($row,"0"));
			$virtuemart_orders_details=$virtuemart_orders_temp['details'];
			
						
			$billing_address=$virtuemart_orders_details['BT']; 
			$shipping_address=$billing_address;
			if(isset($virtuemart_orders_details['ST']))
			$shipping_address=$virtuemart_orders_details['ST'];
			
			//prepare order array
			$this->virtuemart_orders[$counter]=new stdClass();
			$this->virtuemart_orders[$counter]->orderid=$this->GetFieldNumber($row,"0");
			$this->virtuemart_orders[$counter]->order_info["OrderNumber"]=$this->virtuemart_orders[$counter]->orderid;
			
			//shipping details
			$this->virtuemart_orders[$counter]->order_shipping["FirstName"]=$this->Check_Field($shipping_address,"first_name");
			$this->virtuemart_orders[$counter]->order_shipping["LastName"]=$this->Check_Field($shipping_address,"last_name");			
			$this->virtuemart_orders[$counter]->order_shipping["Company"]=$this->Check_Field($shipping_address,"company");
			$this->virtuemart_orders[$counter]->order_shipping["Address1"]=$this->Check_Field($shipping_address,"address_1");
			$this->virtuemart_orders[$counter]->order_shipping["Address2"]=$this->Check_Field($shipping_address,"address_2");
			$this->virtuemart_orders[$counter]->order_shipping["City"]=$this->Check_Field($shipping_address,"city");
			
			//get shipping state name
			$query_state = $db->getQuery(true);
			$query_state
			->select($db->quoteName(array('state_name')))
			->from($db->quoteName('#__virtuemart_states'))
			->where("virtuemart_state_id=".$db->quote($this->Check_Field($shipping_address,"virtuemart_state_id")));
			$db->setQuery($query_state);
			$db->execute();
			$virtuemart_state_res = $db->loadRowList();
			foreach ($virtuemart_state_res as $row_state) 
			{
				$this->virtuemart_orders[$counter]->order_shipping["State"]=$row_state[0];	
			}
			
			$this->virtuemart_orders[$counter]->order_shipping["PostalCode"]=$this->Check_Field($shipping_address,"zip");
			
			//get shipping country name
			$query_country = $db->getQuery(true);
			$query_country
			->select($db->quoteName(array('country_name')))
			->from($db->quoteName('#__virtuemart_countries'))
			->where("virtuemart_country_id=".$db->quote($this->Check_Field($billing_address,"virtuemart_country_id")));
			$db->setQuery($query_country);
			$db->execute();
			$virtuemart_country_res = $db->loadRowList();
			foreach ($virtuemart_country_res as $row_country) 
			{
				$this->virtuemart_orders[$counter]->order_shipping["Country"]=$row_country[0];	
			}
			
			$this->virtuemart_orders[$counter]->order_shipping["Phone"]=$this->Check_Field($shipping_address,"phone_1");
			$this->virtuemart_orders[$counter]->order_shipping["EMail"]=$this->Check_Field($shipping_address,"email");
			
			//billing details
			$this->virtuemart_orders[$counter]->order_billing["FirstName"]=$this->Check_Field($billing_address,"first_name");
			$this->virtuemart_orders[$counter]->order_billing["LastName"]=$this->Check_Field($billing_address,"last_name");
			$this->virtuemart_orders[$counter]->order_billing["Company"]=$this->Check_Field($billing_address,"company");
			$this->virtuemart_orders[$counter]->order_billing["Address1"]=$this->Check_Field($billing_address,"address_1");
			$this->virtuemart_orders[$counter]->order_billing["Address2"]=$this->Check_Field($billing_address,"address_2");
			$this->virtuemart_orders[$counter]->order_billing["City"]=$this->Check_Field($billing_address,"city");
			
			
			//get billing state name
			$query_state_b = $db->getQuery(true);
			$query_state_b
			->select($db->quoteName(array('state_name')))
			->from($db->quoteName('#__virtuemart_states'))
			->where("virtuemart_state_id=".$db->quote($this->Check_Field($billing_address,"virtuemart_state_id")));
			$db->setQuery($query_state_b);
			$db->execute();
			$virtuemart_state_b_res = $db->loadRowList();
			foreach ($virtuemart_state_b_res as $row_state_b) 
			{
				$this->virtuemart_orders[$counter]->order_billing["State"]=$row_state_b[0];	
			}
			
			$this->virtuemart_orders[$counter]->order_billing["PostalCode"]=$this->Check_Field($billing_address,"zip");
			
			
			//get billing country name
			$query_country_b = $db->getQuery(true);
			$query_country_b
			->select($db->quoteName(array('country_name')))
			->from($db->quoteName('#__virtuemart_countries'))
			->where("virtuemart_country_id=".$db->quote($this->Check_Field($billing_address,"virtuemart_country_id")));
			$db->setQuery($query_country_b);
			$db->execute();
			$virtuemart_country_b_res = $db->loadRowList();
			foreach ($virtuemart_country_b_res as $row_country_b) 
			{
				$this->virtuemart_orders[$counter]->order_billing["Country"]=$row_country_b[0];	
			}
			
			
			
			$this->virtuemart_orders[$counter]->order_billing["Phone"]=$this->Check_Field($billing_address,"phone_1");
						
			//order info
			$this->virtuemart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,strtotime($billing_address->created_on));
			$this->virtuemart_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($this->Check_Field($billing_address,"order_salesPrice"));
			$this->virtuemart_orders[$counter]->order_info["Total"]=$this->FormatNumber($this->Check_Field($billing_address,"order_total"));
			$this->virtuemart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->FormatNumber(($this->Check_Field($billing_address,"order_shipment")+$this->Check_Field($billing_address,"order_shipment_tax")));
			$this->virtuemart_orders[$counter]->order_info["ItemsTax"]=$this->FormatNumber($this->Check_Field($billing_address,"order_billTaxAmount"));
			$this->virtuemart_orders[$counter]->order_info["Comments"]=$this->MakeXMLSafe($this->Check_Field($billing_address,"customer_note")); 
			
			
			$this->virtuemart_orders[$counter]->order_info["ShipMethod"]="";
			//Get shipping method
			$query_shipping_method = $db->getQuery(true);
			$query_shipping_method
			->select($db->quoteName(array('shipment_name')))
			->from($db->quoteName('#__virtuemart_shipmentmethods_en_us'))
			->where("virtuemart_shipmentmethod_id=".$db->quote($this->Check_Field($billing_address,"virtuemart_shipmentmethod_id")));
			$db->setQuery($query_shipping_method);
			$db->execute();
			$virtuemart_shipping_method_res = $db->loadRowList(); 
			foreach ($virtuemart_shipping_method_res as $row_shipping_method) 
			{
				$this->virtuemart_orders[$counter]->order_info["ShipMethod"]=$row_shipping_method[0];	
			}
			
			$payment_method="";
			//Get payment method
			$query_payment_method = $db->getQuery(true);
			$query_payment_method
			->select($db->quoteName(array('payment_name')))
			->from($db->quoteName('#__virtuemart_paymentmethods_en_us'))
			->where("virtuemart_paymentmethod_id=".$db->quote($this->Check_Field($billing_address,"virtuemart_paymentmethod_id")));
			$db->setQuery($query_payment_method);
			$db->execute();
			$virtuemart_payment_method_res = $db->loadRowList(); 
			foreach ($virtuemart_payment_method_res as $row_payment_method) 
			{
				$payment_method=$row_payment_method[0];	
			}
			
			$this->virtuemart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($payment_method);
			
						
			if($this->Check_Field($billing_address,"order_status")=="C" && $this->Check_Field($billing_address,"order_status")=="S")
				$this->virtuemart_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$this->virtuemart_orders[$counter]->order_info["PaymentStatus"]=0;
				
			if($this->Check_Field($billing_address,"order_status")=="X" )
				$this->virtuemart_orders[$counter]->order_info["IsCancelled"]=1;
			else
				$this->virtuemart_orders[$counter]->order_info["IsCancelled"]=0;
			
			//Show Order status	
			if($this->Check_Field($billing_address,"order_status")=="S")
				$this->virtuemart_orders[$counter]->order_info["IsShipped"]=1;
			else
				$this->virtuemart_orders[$counter]->order_info["IsShipped"]=0;
			
			
			//Get order products
			$virtuemart_orders_products=$virtuemart_orders_temp['items'];
			$this->virtuemart_orders[$counter]->num_of_products=count($virtuemart_orders_products);
			$items_cost=0;
			
			for($i=0;$i<$this->virtuemart_orders[$counter]->num_of_products;$i++)
			{
				
				$this->virtuemart_orders[$counter]->order_product[$i]["Name"]=$this->Check_Field($virtuemart_orders_products[$i],"order_item_name");
				 $unit_price=$this->FormatNumber($this->Check_Field($virtuemart_orders_products[$i],"product_item_price"));
				$this->virtuemart_orders[$counter]->order_product[$i]["Price"]=$unit_price;
				
				//$this->virtuemart_orders[$counter]->order_product[$i]["ExternalID"]=$this->Check_Field($virtuemart_orders_products[$i],"order_item_sku"); //at present, "order_item_sku" does not have any value in virtuemart db, so we are using "virtuemart_product_id"
				$this->virtuemart_orders[$counter]->order_product[$i]["ExternalID"]=$this->Check_Field($virtuemart_orders_products[$i],"virtuemart_product_id");
				
				$this->virtuemart_orders[$counter]->order_product[$i]["Quantity"]=$this->Check_Field($virtuemart_orders_products[$i],"product_quantity");
				
				$this->virtuemart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($unit_price*$this->Check_Field($virtuemart_orders_products[$i],"product_quantity"));
				
				$items_cost=$items_cost+$this->virtuemart_orders[$counter]->order_product[$i]["Total"];
						
				//Get product attributes
				$attributes="";
				$option_arr=$this->Check_Field($virtuemart_orders_products[$i],"product_attribute");
				
				if($option_arr!="")
				{
					$option_arr_temp=explode(",",stripslashes($option_arr));
				
					foreach($option_arr_temp as $option_row_temp)
					{
					
						$option_row_temp=preg_replace('/[{}]/', '', $option_row_temp);
						$option_row_temp=str_replace("\"","",$option_row_temp);
						$option_row_temp=explode(":",stripslashes($option_row_temp));
						$option_row=$option_row_temp[1];
						$option_row=str_replace("</span><span","</span>:<span",$option_row);
						
						$option_row=strip_tags($option_row);
						
						if($attributes!="")
						$attributes.=",".$option_row;
						else
						$attributes=$option_row;
								
					}
				}		
					
				
				//get product weight
				$product_weight="";
				$product_uom="";
				$query_product_weight = $db->getQuery(true);
				$query_product_weight
				->select($db->quoteName(array('product_weight','product_weight_uom')))
				->from($db->quoteName('#__virtuemart_products'))
				->where("virtuemart_product_id=".$db->quote($this->Check_Field($virtuemart_orders_products[$i],"virtuemart_product_id")));
				$db->setQuery($query_product_weight);
				$db->execute();
				$virtuemart_product_weight_res = $db->loadRowList(); 
				foreach ($virtuemart_product_weight_res as $row_product_weight) 
				{
					
					$product_weight=$row_product_weight[0];	
					$product_uom=$row_product_weight[1];	
				}
				
				$this->virtuemart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->FormatNumber($product_weight*$this->Check_Field($virtuemart_orders_products[$i],"product_quantity"));
				$this->virtuemart_orders[$counter]->order_product[$i]["IndividualProductWeight"]=$this->FormatNumber($product_weight);
				$this->virtuemart_orders[$counter]->order_product[$i]["UOMProductWeight"]=strtoupper($product_uom);
				
				$this->virtuemart_orders[$counter]->order_product[$i]["Notes"]=$attributes;
				
				if($attributes!="")
				$this->virtuemart_orders[$counter]->order_product[$i]["Name"]=$this->virtuemart_orders[$counter]->order_product[$i]["Name"]." (".$attributes.")";
				
			
				 
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
			

			if (isset($this->virtuemart_orders))
				return $this->virtuemart_orders;
			else
                       		return array();  
			
	}
	################################################ Function PrepareOrderStatusString #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareVirtuemartOrderStatusFilter()
	{
			
			$order_status_filter="";
			
			if(VIRTUEMART_RETRIEVE_ORDER_STATUS_1_PENDING==1)//considers queued/pre-authorized orders
			{
				$order_status_filter="  order_status='P' ";
			
			}
			if(VIRTUEMART_RETRIEVE_ORDER_STATUS_2_CONFIRMED_BY_SHOPPER==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" order_status='U' ";
				}
				else
				{
					$order_status_filter.=" OR order_status='U' ";
				}
			
			}
			if(VIRTUEMART_RETRIEVE_ORDER_STATUS_3_CONFIRMED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" order_status='C' ";
				}
				else
				{
					$order_status_filter.=" OR order_status='C'";
				}
			
			}
			if(VIRTUEMART_RETRIEVE_ORDER_STATUS_4_SHIPPED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" order_status='S' ";
				}
				else
				{
					$order_status_filter.=" OR order_status='S'";
				}
			
			}
			if(VIRTUEMART_RETRIEVE_ORDER_STATUS_5_CANCELLED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" order_status='X' ";
				}
				else
				{
					$order_status_filter.=" OR order_status='X'";
				}
			
			}
			if(VIRTUEMART_RETRIEVE_ORDER_STATUS_6_REFUNDED==1)
			{
				if($order_status_filter=="")
				{
					$order_status_filter.=" order_status='R' ";
				}
				else
				{
					$order_status_filter.=" OR order_status='R'";
				}
			
			}
			if($order_status_filter!="")
			$order_status_filter="( ".$order_status_filter." ) and";
			
			
			return $order_status_filter;
			
	}
		
	
}
######################################### End of class ShippingZVirtuemart ###################################################

	// create object & perform tasks based on command
	$obj_shipping_virtuemart=new ShippingZVirtuemart;
	$obj_shipping_virtuemart->ExecuteCommand();

?>