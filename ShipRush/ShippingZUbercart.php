<?php

define("SHIPPINGZUBERCART_VERSION","3.0.7.8833");

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
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZUBERCART_VERSION && SHIPPINGZUBERCART_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZUbercart.php [".SHIPPINGZUBERCART_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}


// Check for Drupal bootstrap file and bootstrap the DB  
define('DRUPAL_ROOT', getcwd());
if(Check_Include_File('includes/bootstrap.inc'))
require('includes/bootstrap.inc');
drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
// Can now access DB with Drupal functions

//Check if Drupal version is 7
if(!defined('VERSION'))
define("VERSION","6");
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");

if(VERSION>6)
{
				############################################## Class ShippingZUbercart ######################################
				class ShippingZUbercart extends ShippingZGenericShoppingCart
				{
					
					//cart specific functions goes here
					
					############################################## Function Check_DB_Access #################################
					//Check Database access
					#######################################################################################################
					
					function Check_DB_Access()
					{
						
						//check if ubercart database can be acessed or not
						$result = db_query('SHOW COLUMNS FROM {uc_orders}');
						
						/* while ($data = db_fetch_array($result)) {
							 print_r($data);
							}*/
						//echo $result;exit;
						
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
						global $databases;
						
						$order_status_filter=$this->PrepareUbercartOrderStatusFilter();
						
						$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
						$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
						
						//Get pending order count based on data range			
						$sql = "SELECT * FROM {uc_orders} WHERE ".$order_status_filter."  ((modified between :datefrom and :dateto) ||(created between :datefrom and :dateto))";
						
						$data=array(':datefrom' => $datefrom_timestamp, ':dateto' =>$dateto_timestamp);
						
						$result = db_query($sql,$data);
						
						return $result->rowCount();
					
					}
					############################################## Function UpdateShippingInfo #################################
					//Update order status
					#######################################################################################################
					function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
					{
						global $databases;
						
						$sql = "SELECT COUNT(*) as total_order FROM {uc_orders} WHERE order_id=:order_id";
						$data=array(':order_id' => $OrderNumber);
						$result = db_query($sql,$data);
						$row = $result->fetchAssoc();
						//check if order number is valid
						if($row ['total_order']>0)
						{
						
							if($ShipDate!="")
								$shipped_on=$ShipDate;
							else
								$shipped_on=date("m/d/Y");
								
							if($Carrier!="")
							{
								$carrier_val=$Carrier;
								$Carrier=" via ".$Carrier;
							}
							
							if($ShipmentType=="")
							$ShipmentType="Custom";
							
							if($ShippingCost=="")
							$ShippingCost=0;
							
							if($Service!="")
							{
								$service_val=$Service;
								$Service=" [".$Service."]";
							}
							
							$TrackingNumberString="";
							if($TrackingNumber!="")
							$TrackingNumberString=", Tracking number $TrackingNumber";
							
							//get shipments
							$sql = "SELECT * FROM {uc_orders} WHERE  order_id=:order_id";
							$data=array(':order_id' => $OrderNumber);
							$result = db_query($sql,$data);
							$row = $result->fetchAssoc();
							$current_order_status=$row['order_status'];
							
							//prepare $comments (appending existing notes)
							$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
							
							//insert into admin order comments
								$data_upd=array(':order_id'=>$OrderNumber,':message'=>$comments,':created'=>time());
								db_query("Insert into {uc_order_admin_comments} set order_id=:order_id, message=:message,created=:created",$data_upd);
							
							//update order table
							if(UBERCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE==1)
							{
								
								$data_upd2=array(':status' =>'completed',":modified"=>time(),':order_id'=>$OrderNumber);
								db_query("update {uc_orders} set order_status=:status,modified=:modified  where order_id=:order_id",$data_upd2);
								$data_upd3=array(':status' =>'completed',":created"=>time(),':order_id'=>$OrderNumber);
								db_query("Insert into  {uc_order_comments} set order_status=:status,created=:created,order_id=:order_id",$data_upd3);
								
								
							}
							else
							{
								if($current_order_status=='pending' || $current_order_status=='payment_received' )
									$change_order_status='processing';
								else if($current_order_status=='processing')
									$change_order_status='completed';
								else
									$change_order_status=$current_order_status;
								
								$data_upd2=array(':status' =>$change_order_status,":modified"=>time(),':order_id'=>$OrderNumber);	
								db_query("update {uc_orders} set order_status=:status, modified=:modified   where order_id=:order_id",$data_upd2);
								$data_upd3=array(':status' =>$change_order_status,":created"=>time(),':order_id'=>$OrderNumber);
								db_query("Insert into  {uc_order_comments} set order_status=:status,created=:created,order_id=:order_id",$data_upd3);
							}	
							if(UBERCART_CREATE_SHIPMENT_AND_PACKAGE==1)
							{
										$sql_shipment_check = "SELECT COUNT(*) as total_shipment FROM {uc_shipments} WHERE order_id=:order_id";
										$data_shipment_check=array(':order_id' => $OrderNumber);
										$result_shipment_check = db_query($sql_shipment_check,$data_shipment_check);
										$row_shipment_check = $result_shipment_check->fetchAssoc();
										if($row_shipment_check['total_shipment']==0)
										{							 
												//Create Shipments
												$data_shipment=array(':order_id'=>$OrderNumber, ':o_first_name'=>$this->GetFieldString($row,"billing_first_name"), ':o_last_name'=>$this->GetFieldString($row,"billing_last_name"), ':o_company'=>$this->GetFieldString($row,"billing_company"), ':o_street1'=>$this->GetFieldString($row,"billing_street1"), ':o_street2'=>$this->GetFieldString($row,"billing_street2"), ':o_city'=>$this->GetFieldString($row,"billing_city"), ':o_zone'=>$this->GetFieldString($row,"billing_zone"), ':o_postal_code'=>$this->GetFieldString($row,"billing_postal_code"), ':o_country'=>$this->GetFieldString($row,"billing_country"), ':d_first_name'=>$this->GetFieldString($row,"delivery_first_name"), ':d_last_name'=>$this->GetFieldString($row,"delivery_last_name"), ':d_company'=>$this->GetFieldString($row,"delivery_company"), ':d_street1'=>$this->GetFieldString($row,"delivery_street1"), ':d_street2'=>$this->GetFieldString($row,"delivery_street2"), ':d_city'=>$this->GetFieldString($row,"delivery_city"), ':d_zone'=>$this->GetFieldString($row,"delivery_zone"), ':d_postal_code'=>$this->GetFieldString($row,"delivery_postal_code"), ':d_country'=>$this->GetFieldString($row,"delivery_country"), ':shipping_method'=>$ShipmentType, ':carrier'=>$carrier_val, ':tracking_number'=>$TrackingNumber, ':accessorials'=>$service_val, ':ship_date'=>time(),':expected_delivery'=>time(), ':cost'=>$ShippingCost);
												
												
														
												 $package_sid = db_query("INSERT INTO {uc_shipments} ( `order_id`, `o_first_name`, `o_last_name`, `o_company`, `o_street1`, `o_street2`, `o_city`, `o_zone`, `o_postal_code`, `o_country`, `d_first_name`, `d_last_name`, `d_company`, `d_street1`, `d_street2`, `d_city`, `d_zone`, `d_postal_code`, `d_country`, `shipping_method`, `carrier`,  `tracking_number`,   `accessorials`, `ship_date`, `expected_delivery` , `cost`) VALUES (:order_id, :o_first_name, :o_last_name, :o_company, :o_street1, :o_street2, :o_city, :o_zone, :o_postal_code, :o_country, :d_first_name, :d_last_name, :d_company, :d_street1, :d_street2, :d_city, :d_zone, :d_postal_code, :d_country, :shipping_method, :carrier,  :tracking_number, :accessorials, :ship_date,  :expected_delivery, :cost)", $data_shipment, array('return' => Database::RETURN_INSERT_ID));
												 
												//Create Packages
												$data_upd_pck=array(':order_id'=>$OrderNumber,':sid'=>$package_sid, ':tracking_number'=>$TrackingNumber);
												$package_id=db_query("Insert into  {uc_packages} set order_id=:order_id,shipping_type='small_package',sid=:sid, tracking_number=:tracking_number",$data_upd_pck, array('return' => Database::RETURN_INSERT_ID));
												
												$product_sql = "select uop.*,up.weight_units from {uc_order_products} uop, {uc_products} up where uop.order_id=:order_id and uop.nid=up.nid";
												$data_get_prod=array(':order_id' => $OrderNumber);
												$product_result = db_query($product_sql,$data_get_prod);
												$i=0;
												$uom_weight="";
												while ($product_row=$product_result->fetchAssoc()) 
												{
													$data_upd_pck_prod=array(':package_id'=>$package_id,':order_product_id'=>$this->GetFieldNumber($product_row,"order_product_id"),':qty'=>$this->GetFieldNumber($product_row,"qty"));
													db_query("Insert into  {uc_packaged_products} set package_id=:package_id, order_product_id=:order_product_id,qty=:qty",$data_upd_pck_prod);
													$i++;
												}
													
										}	
								}
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
						global $databases;
						
						$order_status_filter=$this->PrepareUbercartOrderStatusFilter();
						
						$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
						$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
						
						$search=$order_status_filter." ((modified between :datefrom and :dateto) ||(created between :datefrom and :dateto))";
						
						$data_query_raw=array(':datefrom' => $datefrom_timestamp, ':dateto' =>$dateto_timestamp);
				
						$orders_query_raw = "select * from {uc_orders} where ".$search ." order by order_id DESC";
									  
						$ubercart_orders_res = db_query($orders_query_raw,$data_query_raw);
						$counter=0;
						while ($ubercart_orders_row=$ubercart_orders_res->fetchAssoc()) 
						{
							
							
							//prepare order array
							$this->ubercart_orders[$counter]=new stdClass();
							$this->ubercart_orders[$counter]->orderid=$this->GetFieldNumber($ubercart_orders_row,"order_id");
							
							
							//shipping details
							$this->ubercart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($ubercart_orders_row,"delivery_first_name");
							$this->ubercart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($ubercart_orders_row,"delivery_last_name");
							$this->ubercart_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($ubercart_orders_row,"delivery_company");
							$this->ubercart_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($ubercart_orders_row,"delivery_street1");			
							$this->ubercart_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($ubercart_orders_row,"delivery_street2");
							$this->ubercart_orders[$counter]->order_shipping["City"]=$this->GetFieldString($ubercart_orders_row,"delivery_city");
							//get state code:
							$shipping_state_sql_data=array(':zone_id' => $this->GetFieldString($ubercart_orders_row,"delivery_zone"));
							$shipping_state_sql = "select * from {uc_zones} where zone_id=:zone_id";
							$shipping_state_result = db_query($shipping_state_sql,$shipping_state_sql_data);
							$shipping_state_row=$shipping_state_result->fetchAssoc();
							
							$this->ubercart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($shipping_state_row,"zone_code");
							$this->ubercart_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($ubercart_orders_row,"delivery_postal_code");
							
							//get country code:
							$shipping_country_sql_data=array(':country_id' => $this->GetFieldString($ubercart_orders_row,"delivery_country"));
							$shipping_country_sql = "select * from {uc_countries} where country_id=:country_id";
							$shipping_country_result = db_query($shipping_country_sql,$shipping_country_sql_data);
							$shipping_country_row=$shipping_country_result->fetchAssoc();
							
							$this->ubercart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($shipping_country_row,"country_iso_code_2");
							$this->ubercart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($ubercart_orders_row,"delivery_phone");
							$this->ubercart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($ubercart_orders_row,"primary_email");
							
							//billing details
							$this->ubercart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($ubercart_orders_row,"billing_first_name");
							$this->ubercart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($ubercart_orders_row,"billing_last_name");
							$this->ubercart_orders[$counter]->order_billing["Company"]=$this->GetFieldString($ubercart_orders_row,"billing_company");
							$this->ubercart_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($ubercart_orders_row,"billing_street1");			
							$this->ubercart_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($ubercart_orders_row,"billing_street2");
							$this->ubercart_orders[$counter]->order_billing["City"]=$this->GetFieldString($ubercart_orders_row,"billing_city");
							
							//get state code:
							$shipping_state_sql_data=array(':zone_id' => $this->GetFieldString($ubercart_orders_row,"billing_zone"));
							$shipping_state_sql = "select * from {uc_zones} where zone_id=:zone_id";
							$shipping_state_result = db_query($shipping_state_sql,$shipping_state_sql_data);
							$shipping_state_row=$shipping_state_result->fetchAssoc();
							$this->ubercart_orders[$counter]->order_billing["State"]=$this->GetFieldString($shipping_state_row,"zone_code");
							$this->ubercart_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($ubercart_orders_row,"billing_postal_code");
							
							//get country code:
							$shipping_country_sql_data=array(':country_id' => $this->GetFieldString($ubercart_orders_row,"billing_country"));
							$shipping_country_sql = "select * from {uc_countries} where country_id=:country_id";
							$shipping_country_result = db_query($shipping_country_sql,$shipping_country_sql_data);
							$shipping_country_row=$shipping_country_result->fetchAssoc();
							
							$this->ubercart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($shipping_country_row,"country_iso_code_2");
							$this->ubercart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($ubercart_orders_row,"billing_phone");
							$this->ubercart_orders[$counter]->order_billing["EMail"]=$this->GetFieldString($ubercart_orders_row,"primary_email");
							
							//order info
							$this->ubercart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,$this->GetFieldString($ubercart_orders_row,"created"));
							
							$this->ubercart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($ubercart_orders_row,"order_id");
							
							$this->ubercart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($ubercart_orders_row,"payment_method"));
							//get shipping charges/taxes
							$this->ubercart_orders[$counter]->order_info["ShippingChargesPaid"]=0;
							$this->ubercart_orders[$counter]->order_info["ShipMethod"]="";
							$this->ubercart_orders[$counter]->order_info["ItemsTax"]=0;
							
							$shipping_tax_sql_data=array(':order_id' => $this->GetFieldNumber($ubercart_orders_row,"order_id"));
							$shipping_tax_sql = "select * from {uc_order_line_items} where order_id=:order_id";
							$shipping_tax_result = db_query($shipping_tax_sql,$shipping_tax_sql_data);
							while ($shipping_tax_row=$shipping_tax_result->fetchAssoc()) 
							{
								if($this->GetFieldString($shipping_tax_row,"type")=="shipping")
								{
									$this->ubercart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->FormatNumber($this->GetFieldNumber($shipping_tax_row,"amount"));
									$this->ubercart_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($shipping_tax_row,"title");
								}
								else if($this->GetFieldString($shipping_tax_row,"type")=="tax")
								{
									$this->ubercart_orders[$counter]->order_info["ItemsTax"]=$this->FormatNumber($this->GetFieldNumber($shipping_tax_row,"amount"));
								
								}
								
							}
							
							if($this->GetFieldString($ubercart_orders_row,"order_status")!="pending")
								$this->ubercart_orders[$counter]->order_info["PaymentStatus"]=2;
							else
								$this->ubercart_orders[$counter]->order_info["PaymentStatus"]=0;
							
							//Show Order status	
							if($this->GetFieldString($ubercart_orders_row,"order_status")=="completed")
								$this->ubercart_orders[$counter]->order_info["IsShipped"]=1;
							else
								$this->ubercart_orders[$counter]->order_info["IsShipped"]=0;
							
							//Get Customer Comments
							$order_comment_sql = "select * from {uc_order_comments} where order_id=:order_id order by comment_id asc";
							$order_comment_sql_data=array(':order_id' => $this->ubercart_orders[$counter]->order_info["OrderNumber"]);
							$order_comment_result = db_query($order_comment_sql,$order_comment_sql_data);
							$order_comment_row=$order_comment_result->fetchAssoc(); 
							$this->ubercart_orders[$counter]->order_info["Comments"]=$this->MakeXMLSafe($this->GetFieldString($order_comment_row,"message"));
							
							//Get order products
							$items_cost=0;
							$items_tax=0;
							
							
							$product_sql = "select * from {uc_order_products} where order_id=:order_id";
							$product_sql_data=array(':order_id' => $this->ubercart_orders[$counter]->order_info["OrderNumber"]);
							$product_result = db_query($product_sql,$product_sql_data);
							$i=0;
							while ($product_row=$product_result->fetchAssoc()) 
							{
								$this->ubercart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_row,"title");
								$this->ubercart_orders[$counter]->order_product[$i]["Price"]=$this->GetFieldMoney($product_row,"price");
								$this->ubercart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_row,"model");
								$this->ubercart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_row,"qty");
								$this->ubercart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->GetFieldNumber($product_row,"price")*$this->GetFieldNumber($product_row,"qty"));
								
								$items_cost=$items_cost+$this->ubercart_orders[$counter]->order_product[$i]["Total"];
								$items_tax=$items_tax+0;
								
								
								$this->ubercart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($product_row,"weight")*$this->GetFieldNumber($product_row,"qty");
								
								$i++;
							}
							
							$this->ubercart_orders[$counter]->num_of_products=$i;
							
							$this->ubercart_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($items_cost,2);
							
							$this->ubercart_orders[$counter]->order_info["Total"]=$this->FormatNumber(($items_cost+$this->ubercart_orders[$counter]->order_info["ItemsTax"]+$this->ubercart_orders[$counter]->order_info["ShippingChargesPaid"]));
							
							
							
							
							$counter++;
						}	
						
						
					}
					
					################################### Function GetOrdersByDate($datefrom,$dateto) ######################
					//Get orders based on date range
					#######################################################################################################
					function GetOrdersByDate($datefrom,$dateto)
					{
							
							$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
							
							if (isset($this->ubercart_orders))
								return $this->ubercart_orders;
							else
											return array();  
				
				
							
					}
					
					
					################################################ Function PrepareUbercartOrderStatusFilter #######################
					//Prepare order status string based on settings
					#######################################################################################################
					function PrepareUbercartOrderStatusFilter()
					{
							
							$order_status_filter="";
							
							if(UBERCART_RETRIEVE_ORDER_STATUS_1_PENDING==1)
							{
								$order_status_filter=" order_status='pending' ";
							
							}
							if(UBERCART_RETRIEVE_ORDER_STATUS_2_PAYMENT_RECEIVED==1)
							{
								if($order_status_filter=="")
								{
									$order_status_filter.=" order_status='payment_received' ";
								}
								else
								{
									$order_status_filter.=" OR order_status='payment_received' ";
								}
							
							}
							if(UBERCART_RETRIEVE_ORDER_STATUS_3_PROCESSING==1)
							{
								if($order_status_filter=="")
								{
									$order_status_filter.=" order_status='processing' ";
								}
								else
								{
									$order_status_filter.=" OR order_status='processing' ";
								}
							
							}
							if(UBERCART_RETRIEVE_ORDER_STATUS_4_DELIVERED==1)
							{
								if($order_status_filter=="")
								{
									$order_status_filter.=" order_status='completed' ";
								}
								else
								{
									$order_status_filter.=" OR order_status='completed' ";
								}
							
							}
							
							if($order_status_filter!="")
							$order_status_filter="( ".$order_status_filter." ) and";
							return $order_status_filter;
							
					}
					
					
				}
}//end drupal 7 i.e. ubercart v3
else
{
				############################################## Class ShippingZUbercart ######################################
				class ShippingZUbercart extends ShippingZGenericShoppingCart
				{
					
					//cart specific functions goes here
					
					############################################## Function Check_DB_Access #################################
					//Check Database access
					#######################################################################################################
					
					function Check_DB_Access()
					{
						
						//check if ubercart database can be acessed or not
						$result = db_query('SHOW COLUMNS FROM {uc_orders}');
						
						
						if ($result) 
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
						global $active_db;
						
						$order_status_filter=$this->PrepareUbercartOrderStatusFilter();
						
						$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
						$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
						
						//Get pending order count based on data range			
						$sql = "SELECT COUNT(*) as total_order FROM {uc_orders} WHERE ".$order_status_filter."  ((modified between '%s' and '%s') ||(created between '%s' and '%s'))";
						
						$result = db_query($sql,$datefrom_timestamp,$dateto_timestamp,$datefrom_timestamp,$dateto_timestamp);
						$row = db_fetch_array($result);
						return $row['total_order'];
					
					}
					############################################## Function UpdateShippingInfo #################################
					//Update order status
					#######################################################################################################
					function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='')
					{
						global $active_db;
						
						$sql = "SELECT COUNT(*) as total_order FROM {uc_orders} WHERE order_id='%d'";
						$result = db_query($sql,$OrderNumber);
						$row = db_fetch_array($result);
						//check if order number is valid
						if($row ['total_order']>0)
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
							
							//get shipments
							$sql = "SELECT * FROM {uc_orders} WHERE  order_id='%d'";
							$result = db_query($sql,$OrderNumber);
							$row = db_fetch_array($result);
							$current_order_status=$row['order_status'];
							
							//prepare $comments (appending existing notes)
							$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
							
							//insert into admin order comments
							db_query("Insert into { uc_order_admin_comments} set order_id='%d', message='%s',created='%s'",$OrderNumber,$comments,time());
							
							//update order table
							if(UBERCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE==1)
							{
								db_query("update {uc_orders} set order_status='%s',modified='%s'  where order_id='%d'",'completed',time(),$OrderNumber);
								db_query("Insert into  {uc_order_comments} set order_status='%s',created='%s',order_id='%d'",'completed',time(),$OrderNumber);
								
								
							}
							else
							{
								if($current_order_status=='pending' || $current_order_status=='payment_received' )
									$change_order_status='processing';
								else if($current_order_status=='processing')
									$change_order_status='completed';
								else
									$change_order_status=$current_order_status;
								
								db_query("update {uc_orders} set order_status='%s', modified='%s'   where order_id='%d'",$change_order_status,time(),$OrderNumber);
								db_query("Insert into  {uc_order_comments} set order_status='%s',created='%s',order_id='%d'",$change_order_status,time(),$OrderNumber);
							}	
							
								 
								
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
						global $active_db;
						
						$order_status_filter=$this->PrepareUbercartOrderStatusFilter();
						
						$datefrom_timestamp=$this->GetServerTimeLocal(false,$datefrom);
						$dateto_timestamp=$this->GetServerTimeLocal(false,$dateto);
						
						$search=$order_status_filter." ((modified between '%s' and '%s') ||(created between '%s' and '%s'))";
											
						$orders_query_raw = "select * from {uc_orders} where ".$search ." order by order_id DESC";
									  
						$ubercart_orders_res = db_query($orders_query_raw,$datefrom_timestamp,$dateto_timestamp,$datefrom_timestamp,$dateto_timestamp);
						$counter=0;
						
						while ($ubercart_orders_row=db_fetch_array($ubercart_orders_res)) 
						{
							
							$this->ubercart_orders[$counter]=new stdClass();
							//prepare order array
							$this->ubercart_orders[$counter]->orderid=$this->GetFieldNumber($ubercart_orders_row,"order_id");
							
							
							//shipping details
							$this->ubercart_orders[$counter]->order_shipping["FirstName"]=$this->GetFieldString($ubercart_orders_row,"delivery_first_name");
							$this->ubercart_orders[$counter]->order_shipping["LastName"]=$this->GetFieldString($ubercart_orders_row,"delivery_last_name");
							$this->ubercart_orders[$counter]->order_shipping["Company"]=$this->GetFieldString($ubercart_orders_row,"delivery_company");
							$this->ubercart_orders[$counter]->order_shipping["Address1"]=$this->GetFieldString($ubercart_orders_row,"delivery_street1");			
							$this->ubercart_orders[$counter]->order_shipping["Address2"]=$this->GetFieldString($ubercart_orders_row,"delivery_street2");
							$this->ubercart_orders[$counter]->order_shipping["City"]=$this->GetFieldString($ubercart_orders_row,"delivery_city");
							//get state code:
							$shipping_state_sql = "select * from {uc_zones} where zone_id='%d'";
							$shipping_state_result = db_query($shipping_state_sql,$this->GetFieldString($ubercart_orders_row,"delivery_zone"));
							$shipping_state_row=db_fetch_array($shipping_state_result);
							
							$this->ubercart_orders[$counter]->order_shipping["State"]=$this->GetFieldString($shipping_state_row,"zone_code");
							$this->ubercart_orders[$counter]->order_shipping["PostalCode"]=$this->GetFieldString($ubercart_orders_row,"delivery_postal_code");
							
							//get country code:
							$shipping_country_sql = "select * from {uc_countries} where country_id='%d'";
							$shipping_country_result = db_query($shipping_country_sql,$this->GetFieldString($ubercart_orders_row,"delivery_country"));
							$shipping_country_row=db_fetch_array($shipping_country_result);
							
							$this->ubercart_orders[$counter]->order_shipping["Country"]=$this->GetFieldString($shipping_country_row,"country_iso_code_2");
							$this->ubercart_orders[$counter]->order_shipping["Phone"]=$this->GetFieldString($ubercart_orders_row,"delivery_phone");
							$this->ubercart_orders[$counter]->order_shipping["EMail"]=$this->GetFieldString($ubercart_orders_row,"primary_email");
							
							//billing details
							$this->ubercart_orders[$counter]->order_billing["FirstName"]=$this->GetFieldString($ubercart_orders_row,"billing_first_name");
							$this->ubercart_orders[$counter]->order_billing["LastName"]=$this->GetFieldString($ubercart_orders_row,"billing_last_name");
							$this->ubercart_orders[$counter]->order_billing["Company"]=$this->GetFieldString($ubercart_orders_row,"billing_company");
							$this->ubercart_orders[$counter]->order_billing["Address1"]=$this->GetFieldString($ubercart_orders_row,"billing_street1");			
							$this->ubercart_orders[$counter]->order_billing["Address2"]=$this->GetFieldString($ubercart_orders_row,"billing_street2");
							$this->ubercart_orders[$counter]->order_billing["City"]=$this->GetFieldString($ubercart_orders_row,"billing_city");
							
							//get state code:
							$shipping_state_sql = "select * from {uc_zones} where zone_id='%d'";
							$shipping_state_result = db_query($shipping_state_sql,$this->GetFieldString($ubercart_orders_row,"billing_zone"));
							$shipping_state_row=db_fetch_array($shipping_state_result);
							$this->ubercart_orders[$counter]->order_billing["State"]=$this->GetFieldString($shipping_state_row,"zone_code");
							$this->ubercart_orders[$counter]->order_billing["PostalCode"]=$this->GetFieldString($ubercart_orders_row,"billing_postal_code");
							
							//get country code:
							$shipping_country_sql = "select * from {uc_countries} where country_id='%d'";
							$shipping_country_result = db_query($shipping_country_sql,$this->GetFieldString($ubercart_orders_row,"billing_country"));
							$shipping_country_row=db_fetch_array($shipping_country_result);
							
							$this->ubercart_orders[$counter]->order_billing["Country"]=$this->GetFieldString($shipping_country_row,"country_iso_code_2");
							$this->ubercart_orders[$counter]->order_billing["Phone"]=$this->GetFieldString($ubercart_orders_row,"billing_phone");
							$this->ubercart_orders[$counter]->order_billing["EMail"]=$this->GetFieldString($ubercart_orders_row,"primary_email");
							
							//order info
							$this->ubercart_orders[$counter]->order_info["OrderDate"]=$this->ConvertServerTimeToUTC(true,$this->GetFieldString($ubercart_orders_row,"created"));
							
							$this->ubercart_orders[$counter]->order_info["OrderNumber"]=$this->GetFieldNumber($ubercart_orders_row,"order_id");
							
							$this->ubercart_orders[$counter]->order_info["PaymentType"]=$this->ConvertPaymentType($this->GetFieldString($ubercart_orders_row,"payment_method"));
							//get shipping charges/taxes
							$this->ubercart_orders[$counter]->order_info["ShippingChargesPaid"]=0;
							$this->ubercart_orders[$counter]->order_info["ShipMethod"]="";
							$this->ubercart_orders[$counter]->order_info["ItemsTax"]=0;
							
							$shipping_tax_sql = "select * from {uc_order_line_items} where order_id='%d'";
							$shipping_tax_result = db_query($shipping_tax_sql, $this->GetFieldNumber($ubercart_orders_row,"order_id"));
							while ($shipping_tax_row=db_fetch_array($shipping_tax_result)) 
							{
								if($this->GetFieldString($shipping_tax_row,"type")=="shipping")
								{
									$this->ubercart_orders[$counter]->order_info["ShippingChargesPaid"]=$this->FormatNumber($this->GetFieldNumber($shipping_tax_row,"amount"));
									$this->ubercart_orders[$counter]->order_info["ShipMethod"]=$this->GetFieldString($shipping_tax_row,"title");
								}
								else if($this->GetFieldString($shipping_tax_row,"type")=="tax")
								{
									$this->ubercart_orders[$counter]->order_info["ItemsTax"]=$this->FormatNumber($this->GetFieldNumber($shipping_tax_row,"amount"));
								
								}
								
							}
							
							if($this->GetFieldString($ubercart_orders_row,"order_status")!="pending")
								$this->ubercart_orders[$counter]->order_info["PaymentStatus"]=2;
							else
								$this->ubercart_orders[$counter]->order_info["PaymentStatus"]=0;
							
							//Show Order status	
							if($this->GetFieldString($ubercart_orders_row,"order_status")=="completed")
								$this->ubercart_orders[$counter]->order_info["IsShipped"]=1;
							else
								$this->ubercart_orders[$counter]->order_info["IsShipped"]=0;
							
							//Get Customer Comments
							$order_comment_sql = "select * from {uc_order_comments} where order_id='%d' order by comment_id asc";
							$order_comment_result = db_query($order_comment_sql,$this->ubercart_orders[$counter]->order_info["OrderNumber"]);
							$order_comment_row=db_fetch_array($order_comment_result); 
							$this->ubercart_orders[$counter]->order_info["Comments"]=$this->MakeXMLSafe($this->GetFieldString($order_comment_row,"message"));
							
							//Get order products
							$items_cost=0;
							$items_tax=0;
							
							
							$product_sql = "select uop.*,up.weight_units from {uc_order_products} uop, {uc_products} up , {node} n where uop.order_id='%d' and uop.nid=n.nid and n.vid=up.vid";
							$product_result = db_query($product_sql,$this->ubercart_orders[$counter]->order_info["OrderNumber"]);
							$i=0;
							$uom_weight="";
							while ($product_row=db_fetch_array($product_result)) 
							{
								$this->ubercart_orders[$counter]->order_product[$i]["Name"]=$this->GetFieldString($product_row,"title");
								$this->ubercart_orders[$counter]->order_product[$i]["Price"]=$this->GetFieldMoney($product_row,"price");
								$this->ubercart_orders[$counter]->order_product[$i]["ExternalID"]=$this->GetFieldString($product_row,"model");
								$this->ubercart_orders[$counter]->order_product[$i]["Quantity"]=$this->GetFieldNumber($product_row,"qty");
								$this->ubercart_orders[$counter]->order_product[$i]["Total"]=$this->FormatNumber($this->GetFieldNumber($product_row,"price")*$this->GetFieldNumber($product_row,"qty"));
								
								$items_cost=$items_cost+$this->ubercart_orders[$counter]->order_product[$i]["Total"];
								$items_tax=$items_tax+0;
								
								$uom_weight=$this->GetFieldNumber($product_row,"weight_units");
								//Product individual weight
								$this->ubercart_orders[$counter]->order_product[$i]["IndividualProductWeight"]=$this->GetFieldNumber($product_row,"weight");
								$this->ubercart_orders[$counter]->order_product[$i]["Total_Product_Weight"]=$this->GetFieldNumber($product_row,"weight")*$this->GetFieldNumber($product_row,"qty");
								$this->ubercart_orders[$counter]->order_product[$i]["UOMProductWeight"]=strtoupper($this->GetFieldNumber($product_row,"weight_units"));
								$i++;
							}
							
							$this->ubercart_orders[$counter]->num_of_products=$i;
							
							$this->ubercart_orders[$counter]->order_info["ItemsTotal"]=$this->FormatNumber($items_cost,2);
							
							$this->ubercart_orders[$counter]->order_info["Total"]=$this->FormatNumber(($items_cost+$this->ubercart_orders[$counter]->order_info["ItemsTax"]+$this->ubercart_orders[$counter]->order_info["ShippingChargesPaid"]));
							
							$this->ubercart_orders[$counter]->order_info["UOMWeight"]=strtoupper($uom_weight);
							
							
							$counter++;
						}	
						
						
					}
					
					################################### Function GetOrdersByDate($datefrom,$dateto) ######################
					//Get orders based on date range
					#######################################################################################################
					function GetOrdersByDate($datefrom,$dateto)
					{
							
							$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
							
							if (isset($this->ubercart_orders))
								return $this->ubercart_orders;
							else
											return array();  
				
				
							
					}
					
					
					################################################ Function PrepareUbercartOrderStatusFilter #######################
					//Prepare order status string based on settings
					#######################################################################################################
					function PrepareUbercartOrderStatusFilter()
					{
							
							$order_status_filter="";
							
							if(UBERCART_RETRIEVE_ORDER_STATUS_1_PENDING==1)
							{
								$order_status_filter=" order_status='pending' ";
							
							}
							if(UBERCART_RETRIEVE_ORDER_STATUS_2_PAYMENT_RECEIVED==1)
							{
								if($order_status_filter=="")
								{
									$order_status_filter.=" order_status='payment_received' ";
								}
								else
								{
									$order_status_filter.=" OR order_status='payment_received' ";
								}
							
							}
							if(UBERCART_RETRIEVE_ORDER_STATUS_3_PROCESSING==1)
							{
								if($order_status_filter=="")
								{
									$order_status_filter.=" order_status='processing' ";
								}
								else
								{
									$order_status_filter.=" OR order_status='processing' ";
								}
							
							}
							if(UBERCART_RETRIEVE_ORDER_STATUS_4_DELIVERED==1)
							{
								if($order_status_filter=="")
								{
									$order_status_filter.=" order_status='completed' ";
								}
								else
								{
									$order_status_filter.=" OR order_status='completed' ";
								}
							
							}
							
							if($order_status_filter!="")
							$order_status_filter="( ".$order_status_filter." ) and";
							return $order_status_filter;
							
					}
					
					
				}
}//end earlier ubercart versions
######################################### End of class ShippingZUbercart ###################################################

	//create object & perform tasks based on command
	$obj_shipping_ubercart=new ShippingZUbercart;
	$obj_shipping_ubercart->ExecuteCommand();	

?>