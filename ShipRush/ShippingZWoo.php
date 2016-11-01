<?php

define("SHIPPINGZWOO_VERSION","3.0.7.8833");

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

// Last mod to this file: $Change: 77772 $

// Function for checking Include Files
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

// Check for ShippingZ integration files
if(Check_Include_File("ShippingZSettings.php"))
include("ShippingZSettings.php");
if(Check_Include_File("ShippingZClasses.php"))
include("ShippingZClasses.php");
if(Check_Include_File("ShippingZMessages.php"))
include("ShippingZMessages.php");


// TEST that all the files are same version
if(!(SHIPPINGZCLASSES_VERSION==SHIPPINGZWOO_VERSION && SHIPPINGZWOO_VERSION==SHIPPINGZMESSAGES_VERSION))
{
	echo "File version mismatch<br>";
	echo "ShippingZClasses.php [".SHIPPINGZCLASSES_VERSION."]<br>";
	echo "ShippingZWoo.php [".SHIPPINGZWOO_VERSION."]<br>";
	echo "ShippingZMessages.php [".SHIPPINGZMESSAGES_VERSION."]<br>";
	echo "Please, make sure all of the above files are same version.";
	exit;
}

############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_error_handler("ShippingZ_Exception_Error_Handler");

//include required wp file
if(Check_Include_File("wp-load.php"))
include("wp-load.php");

//Check Permalink structure
$port_number="";
$updated_host="";
if(strpos(DB_HOST, ':') !== false) {
  $split_host=explode(":",DB_HOST);
  $updated_host=$split_host[0];
  $port_number=$split_host[1];
} 

if($port_number!="")
$db_pdo=new PDO("mysql:host=".$updated_host.";port=".$port_number.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
else
$db_pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);

global $wpdb,$product_weight_unit;
											
$sql= "Select * from ".$wpdb->prefix."options where option_name = :option_name and option_value=:option_value";
$permalink_res = $db_pdo->prepare($sql);
$data=array(':option_name' => "permalink_structure",':option_value'=>"");
$permalink_res->execute($data);																		
if($permalink_res->rowCount()==1 && check_woo_version())
{
	echo "Error #ZF41105 : It seems you are using default WP Permalink Settings...Please, set this to Post name and save.";
	exit;
}

try
  {	
		$sql_weight_unit= "Select option_value from ".$wpdb->prefix."options where option_name = :option_name";
		$weight_unit_res = $db_pdo->prepare($sql_weight_unit);
		$data=array(':option_name' => "woocommerce_weight_unit");
		$weight_unit_res->execute($data);
		foreach($weight_unit_res as $weight_unit_attr)
		{
			$product_weight_unit=$weight_unit_attr['option_value'];																		
		}
  }
  
catch(Exception $e)
   {
 		echo "Error #ZF41618 : Weight unit is missing";
		exit;
   }
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
################################################ Function convert_dim_unit #######################
//converts dim unit to desired units
function convert_dim_unit($from_unit)
{
	
	$from_unit=trim($from_unit);
	
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
########################################### Function get_actual_order_id ##############################
function get_actual_order_id($OrderNumber)
{
	global $wpdb;
	//Check if sequencial order number plugin is installed
	$args = array(
		'meta_key' => '_order_number',
		'meta_value' => $OrderNumber,
		'post_type' => 'shop_order'
	);
	$order_posts = get_posts($args);
	if(is_array($order_posts) && count($order_posts)>0)
	{
		$OrderNumber=$order_posts[0]->ID; //Find out actual order id
	}
	return $OrderNumber;
}
################################################ Function check_woo_version ###########################
//checks if woocommerce version is 2.1 or higher or lower
#######################################################################################################
function check_woo_version() 
{
	
	global $woocommerce;
	
	if(!isset($woocommerce))
	$woocommerce=new Woocommerce;
	
	if(version_compare( $woocommerce->version, '2.1', ">=" ) ) {
	  return true;
	}
	return false;
}

if(check_woo_version())
{
		//Use API methods
		define("MaxCount",50);
		############################################## Class ShippingZWoo ######################################
		class ShippingZWoo extends ShippingZGenericShoppingCart
		{
			
			//cart specific functions goes here
			######################################## Function EXECUTE_CURL ######################################
			function EXECUTE_API_COMMAND($method,$api_params=array(),$force_POST=0)
			{
				global $woocommerce;
				
				$ch = curl_init();
				
				if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!="off") 
				{
				  curl_setopt( $ch, CURLOPT_USERPWD, WOO_CONSUMER_KEY . ":" . WOO_CONSUMER_SECRET);
				  $api_params['consumer_key'] = WOO_CONSUMER_KEY;
				  $api_params['consumer_secret'] = WOO_CONSUMER_SECRET;
				}
				else
				{
					//Parameters related to Authentication
					$api_params['oauth_consumer_key'] = WOO_CONSUMER_KEY;
					$api_params['oauth_timestamp'] = time();	
					$api_params['oauth_nonce'] = sha1(microtime(true).mt_rand(10000,90000));
					$api_params['oauth_signature_method'] = 'HMAC-SHA256';
					
					/******************** generate auth signature ******************************/
					//build query string as expected by woocommerce
					$query_params_for_sign = array();
					
					if(version_compare( $woocommerce->version, '2.1.7', ">=" ) )
					{
						$api_params_salt=$api_params;
						
						$api_params_salt=$this->normalize_woo_values( $api_params_salt );
						uksort( $api_params_salt, 'strcmp' );
						
						foreach ( $api_params_salt as $woo_key => $woo_value ) 
						{
							$query_params_for_sign[] = $woo_key . '%3D' . $woo_value; 
						}
					}
					else
					{
						$api_params=$this->normalize_woo_values( $api_params );
						uksort( $api_params, 'strcmp' );
						
						foreach ( $api_params as $woo_key => $woo_value ) 
						{
							$query_params_for_sign[] = $woo_key . '%3D' . $woo_value; 
						}
					
					}
				
					$api_query_string = implode( '%26', $query_params_for_sign ); 
			
					//Create a salt	following woocommerce steps
					if ($force_POST) 
					{
						$woo_salt = 'POST&' .rawurlencode(get_woocommerce_api_url( '' ) .$method). '&' . $api_query_string;
					}
					else
					{
						$woo_salt = 'GET&' .rawurlencode(get_woocommerce_api_url( '' ) .$method). '&' . $api_query_string;
					}
						 
					// Generate signature 
					$woo_signature = hash_hmac('SHA256', $woo_salt, WOO_CONSUMER_SECRET, true);

					 
					$woo_auth_signature = base64_encode($woo_signature);
					
					$api_params['oauth_signature'] = $woo_auth_signature;
				  
				}
			
				$api_params_formatted = null;
				if ( is_array( $api_params ) && isset( $api_params )) {
					$api_params_formatted = '?' . http_build_query( $api_params );
				} 
				
				curl_setopt($ch, CURLOPT_URL, get_woocommerce_api_url( '' ).$method. $api_params_formatted);
				curl_setopt($ch, CURLOPT_TIMEOUT, 120);
				curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 120 );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
				
				if ($force_POST) //Required for update order command
				{
					curl_setopt( $ch, CURLOPT_POST, true );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $api_params ) );
				} 
				 
				$response = curl_exec($ch); 
				
				if($response === false)
				{
					$http_code =curl_getinfo($ch, CURLINFO_HTTP_CODE);
					
					$this->CheckAndOverrideErrorMessage('Curl error: ' . curl_error($ch).'<br>Response Code:'.$http_code);
					
					curl_close($ch);
					
				}
				else
				{
					curl_close($ch);
					
					$response = json_decode(trim($response));
					return $response;
				}
				
			
			}
			######################################### Function normalize_parameters() ########################
			//Normalize 
			function normalize_woo_values( $parameters ) 
			{
					global $woocommerce;
					
					$normalized_parameters = array();
			
					foreach ( $parameters as $key => $value ) {
			
						// percent symbols (%) must be double-encoded for higher woocommerce versions
						if(version_compare( $woocommerce->version, '2.1.7', ">=" ) )
						{
						    $key   = str_replace( '%', '%25', rawurlencode( rawurldecode( $key ) ) );
						    $value = str_replace( '%', '%25', rawurlencode( rawurldecode( $value ) ) );
						}
						else
						{
							$key   = rawurlencode( rawurldecode( $key ) ) ;
							$value = rawurlencode( rawurldecode( $value ) ) ;
						}
			
						$normalized_parameters[ $key ] = $value;
					}
			
					return $normalized_parameters;
			}
			############################################ API methods ###########################################
			function get_woo_orders_count() 
			{
				return $this->EXECUTE_API_COMMAND( 'orders/count' );
			}
			function get_woo_orders( $api_params = array() ) 
			{
				return $this->EXECUTE_API_COMMAND( 'orders', $api_params );
			}
			function get_woo_product( $product_id ,$api_params = array()) 
			{
				return $this->EXECUTE_API_COMMAND( 'products/' . $product_id,$api_params );
			}
			function get_woo_order( $order_id,$api_params = array() ) 
			{
				return $this->EXECUTE_API_COMMAND( 'orders/' . $order_id , $api_params); 
			}
			function update_woo_order( $order_id, $api_params = array()) 
			{
				return $this->EXECUTE_API_COMMAND( 'orders/' . $order_id, $api_params,1 );
			}
			############################################## Function Check_DB_Access #################################
			//Check Database access(for Woocommerce everything will be done using API so, we don't need database access.But need to check if API credentials are set properly)
			#######################################################################################################
			
			function Check_DB_Access()
			{
				$res=$this->get_woo_orders_count();
				if(isset($res->errors))
				{
					$this->SetXmlError(1, $res->errors[0]->message);
				}
				else
				{
					$this->display_msg=DB_SUCCESS_MSG;
				}
				
			}
			
			############################################## Function GetOrderCountByDate #################################
			//Get order count
			#######################################################################################################
			function GetOrderCountByDate($datefrom,$dateto)
			{
					
				//Get order count based on data range
				$order_array_onhold=array();
				$order_array_pending=array();
				$order_array_processing=array();
				$order_array_complete=array();
				$order_array_cancelled=array();
				$onhold_count=0;
				$pending_count=0;
				$processing_count=0;
				$completed_count=0;
				$cancelled_count=0;
				
				if(WOO_RETRIEVE_ORDER_STATUS_0_ON_HOLD==1)
				{
					$order_array_onhold=$this->get_woo_orders( array( 'fields' => 'id','status' => 'on-hold','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					
					if(isset($order_array_onhold->orders))
					$onhold_count=count($order_array_onhold->orders);
					unset($order_array_onhold);
							   
				}
				if(WOO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
				{
					$order_array_pending=$this->get_woo_orders( array( 'fields' => 'id','status' => 'pending','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_pending->orders))
					$pending_count=count($order_array_pending->orders);
					unset($order_array_pending);
							   
				}
				if(WOO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
				{
					$order_array_processing=$this->get_woo_orders( array( 'fields' => 'id','status' => 'processing','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_processing->orders))
					$processing_count=count($order_array_processing->orders);
					unset($order_array_processing);
							   
				}
				if(WOO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
				{
					$order_array_complete=$this->get_woo_orders( array( 'fields' => 'id','status' => 'completed','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_complete->orders))
					$completed_count=count($order_array_complete->orders);
					unset($order_array_complete);
							   
				}
				if(WOO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
				{
					$order_array_cancelled=$this->get_woo_orders( array( 'fields' => 'id','status' => 'cancelled','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_cancelled->orders))
					$cancelled_count=count($order_array_cancelled->orders);
					unset($order_array_cancelled);
							   
				}
				return  ($onhold_count+$pending_count+$processing_count+$completed_count+$cancelled_count);
			
			}
			############################################## Function UpdateShippingInfo #################################
			//Update order status (At this point REST API only allows to update order status)
			#######################################################################################################
			function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
			{
					
				global $wpdb;
				
				$OrderNumber=get_actual_order_id($OrderNumber);
				
				$res=$this->get_woo_order($OrderNumber,array( 'fields' => 'status'));
				
				if(isset($res->errors))
				{
					$this->SetXmlError(1, $res->errors[0]->message);
				}
				else
				{
					//update order status and comments using direct method
					$current_order_status=$res->order->status;
					
					if(WOO_SHIPPED_STATUS_SET_TO_STATUS_3_COMPLETE==1)
					{
						$change_order_status="completed";
					}
					else
					{   
						if(strtolower($current_order_status)=="on-hold")
							$change_order_status="pending";
						else if(strtolower($current_order_status)=="pending")
							$change_order_status="processing";
						else if(strtolower($current_order_status)=="processing")
							$change_order_status="completed";
						else
						$change_order_status=$current_order_status;
					}
										
					 if($ShipDate!="")
						$shipped_on=$ShipDate;
					else
						$shipped_on=date("m/d/Y");
						
					if($Carrier!="")
					{
						$original_carrier=$Carrier;
						$Carrier=" via ".$Carrier;
					}
					
					if($Service!="")
					$Service=" [".$Service."]";
					
					$TrackingNumberString="";
					if($TrackingNumber!="")
					$TrackingNumberString=", Tracking number $TrackingNumber";
										
					//prepare $comments & save it
					$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
					
					$woo_order_data = new WC_Order($OrderNumber);
						
					if(!defined("SHIPMENT_TRACKING_MODULE"))
					define("SHIPMENT_TRACKING_MODULE","0");
					
					// Update tracking information
					if(SHIPMENT_TRACKING_MODULE)
					{
						update_post_meta( $OrderNumber, '_tracking_provider', strtolower($original_carrier));
						update_post_meta( $OrderNumber, '_tracking_number', $TrackingNumber );
						update_post_meta( $OrderNumber, '_date_shipped', time() );
					}
									
					if(WOO_TRACKING_NOTES_UPDATE_ONLY)
					{
						$woo_order_data->add_order_note($comments);
					}
					else
					{
						$woo_order_data->update_status($change_order_status, $comments );
					}
					
										
					$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success")); 
				}
			}
			############################################## Function Fetch_DB_Orders #################################
			//Perform Database query & fetch orders based on date range
			#######################################################################################################
			
			function Fetch_DB_Orders($datefrom,$dateto)
			{
				global $product_weight_unit;
				//Get order count based on data range
				$order_array_onhold=array();
				$order_array_pending=array();
				$order_array_processing=array();
				$order_array_complete=array();
				$order_array_cancelled=array();
				$order_arrays=array();
				
				if(WOO_RETRIEVE_ORDER_STATUS_0_ON_HOLD==1)
				{
					$order_array_onhold_temp=$this->get_woo_orders( array('status' => 'on-hold','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_onhold_temp->orders))
					$order_array_onhold=$order_array_onhold_temp->orders;
							   
				}
				if(WOO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
				{
					$order_array_pending_temp=$this->get_woo_orders( array('status' => 'pending','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_pending_temp->orders))
					$order_array_pending=$order_array_pending_temp->orders;
							   
				}
				if(WOO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
				{
					$order_array_processing_temp=$this->get_woo_orders( array('status' => 'processing','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_processing_temp->orders))
					$order_array_processing=$order_array_processing_temp->orders;
					 
				}
				if(WOO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1)
				{
					$order_array_complete_temp=$this->get_woo_orders( array('status' => 'completed','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_complete_temp->orders))
					$order_array_complete=$order_array_complete_temp->orders;
										   
				}
				if(WOO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
				{
					$order_array_cancelled_temp=$this->get_woo_orders( array('status' => 'cancelled','filter[limit]'=>MaxCount,'filter[updated_at_min]'=>$datefrom,'filter[updated_at_max]'=>$dateto) );
					if(isset($order_array_cancelled_temp->orders))
					$order_array_cancelled=$order_array_cancelled_temp->orders;
												   
				}
				
				$order_arrays=array_merge($order_array_onhold,$order_array_pending,$order_array_processing,$order_array_complete,$order_array_cancelled);
				unset($order_array_onhold);	
				unset($order_array_pending);	
				unset($order_array_processing);	
				unset($order_array_complete);
				unset($order_array_cancelled);
				
				$counter=0;
				$uom_weight="";
				
				foreach($order_arrays as $key=>$orders)
				{
							
						if(isset($orders->order_number))			
						$order_id=trim(str_replace('#', '', $orders->order_number));
													
						//prepare order array
						$this->woo_orders[$counter]=new stdClass();
						$this->woo_orders[$counter]->orderid=$order_id;
						$this->woo_orders[$counter]->order_info['PkgLength']="";
												
						//billing details
						$billing_address=array();
						$billing_address_arr=$orders->billing_address;
										
						$this->woo_orders[$counter]->order_billing["FirstName"]=$this->Check_Field($billing_address_arr,'first_name');
						$this->woo_orders[$counter]->order_billing["LastName"]=$this->Check_Field($billing_address_arr,'last_name');
						$this->woo_orders[$counter]->order_billing["Company"]=$this->Check_Field($billing_address_arr,'company');
						$this->woo_orders[$counter]->order_billing["Address1"]=$this->Check_Field($billing_address_arr,'address_1');
						$this->woo_orders[$counter]->order_billing["Address2"]=$this->Check_Field($billing_address_arr,'address_2');
						$this->woo_orders[$counter]->order_billing["City"]=$this->Check_Field($billing_address_arr,'city');
						$this->woo_orders[$counter]->order_billing["State"]=$this->Check_Field($billing_address_arr,'state');
						$this->woo_orders[$counter]->order_billing["PostalCode"]=$this->Check_Field($billing_address_arr,'postcode');
						$this->woo_orders[$counter]->order_billing["Country"]=$this->Check_Field($billing_address_arr,'country');
						$this->woo_orders[$counter]->order_billing["Phone"]=$this->Check_Field($billing_address_arr,'phone');
						
						//shipping details
						$shipping_address=array();
						$shipping_address_arr=$orders->shipping_address;
									
						$this->woo_orders[$counter]->order_shipping["FirstName"]=$this->Check_Field($shipping_address_arr,'first_name');
						$this->woo_orders[$counter]->order_shipping["LastName"]=$this->Check_Field($shipping_address_arr,'last_name');
						$this->woo_orders[$counter]->order_shipping["Company"]=$this->Check_Field($shipping_address_arr,'company');
						$this->woo_orders[$counter]->order_shipping["Address1"]=$this->Check_Field($shipping_address_arr,'address_1');
						$this->woo_orders[$counter]->order_shipping["Address2"]=$this->Check_Field($shipping_address_arr,'address_2');
						$this->woo_orders[$counter]->order_shipping["City"]=$this->Check_Field($shipping_address_arr,'city');
						$this->woo_orders[$counter]->order_shipping["State"]=$this->Check_Field($shipping_address_arr,'state');
						$this->woo_orders[$counter]->order_shipping["PostalCode"]=$this->Check_Field($shipping_address_arr,'postcode');
						$this->woo_orders[$counter]->order_shipping["Country"]=	$this->Check_Field($shipping_address_arr,'country');
						$this->woo_orders[$counter]->order_shipping["Phone"]=$this->Check_Field($billing_address_arr,'phone');
						$this->woo_orders[$counter]->order_shipping["EMail"]="";
						if(isset($orders->customer->email))
						$this->woo_orders[$counter]->order_shipping["EMail"]=$orders->customer->email;
										
						//order info
						$order_date_actual = new DateTime($orders->created_at);
						$this->woo_orders[$counter]->order_info["OrderDate"]= $order_date_actual->format('Y-m-d\TH:i:00\Z');
						
						
									
						$this->woo_orders[$counter]->order_info["ItemsTotal"]=number_format($orders->subtotal,2,'.','');
						$this->woo_orders[$counter]->order_info["Total"]=number_format($orders->total,2,'.','');
						$this->woo_orders[$counter]->order_info["ItemsTax"]=number_format($orders->total_tax,2,'.','');
							
							
						$this->woo_orders[$counter]->order_info["OrderNumber"]=$order_id;
						$this->woo_orders[$counter]->order_info["PaymentType"]=$orders->payment_details->method_title;
						$this->woo_orders[$counter]->order_info["ShippingChargesPaid"]=number_format($orders->total_shipping,2,'.','');
						$this->woo_orders[$counter]->order_info["ShipMethod"]=$orders->shipping_methods;
						$this->woo_orders[$counter]->order_info["Comments"]=$orders->note;			
			
						if($orders->status!="on-hold" && $orders->status!="pending")
							$this->woo_orders[$counter]->order_info["PaymentStatus"]=2;
						else
							$this->woo_orders[$counter]->order_info["PaymentStatus"]=0;
						
						//Show Order status
						if($orders->status=="completed")
							$this->woo_orders[$counter]->order_info["IsShipped"]=1;
						else
							$this->woo_orders[$counter]->order_info["IsShipped"]=0;
							
						//show if cancelled
						if($orders->status=="cancelled")
							$this->woo_orders[$counter]->order_info["IsCancelled"]=1;
						else
							$this->woo_orders[$counter]->order_info["IsCancelled"]=0;
							
										
						$actual_number_of_products=0;
						$dim_unit="";
						for($i=0;$i<count($orders->line_items);$i++)
						{
						
						$additional_product_arr_temp=$this->get_woo_product( $orders->line_items[$i]->product_id,"");
						$attributes_string="";
						if(isset($additional_product_arr_temp->product))
						{
							$additional_product_arr=$additional_product_arr_temp->product;
							$attributes_arr=$additional_product_arr->attributes;
							
							
												
							foreach($attributes_arr as $key=>$attributes)
							{
								if(isset($attributes->option))
								{
										global $port_number, $updated_host;		
										if($port_number!="")
										$db_pdo=new PDO("mysql:host=".$updated_host.";port=".$port_number.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
										else
										$db_pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
	
										global $wpdb;
												
										$sql= "Select * from ".$wpdb->prefix."woocommerce_order_itemmeta where order_item_id = :order_item_id and meta_key=:meta_key";
										
										$order_attr_res = $db_pdo->prepare($sql);
										
										$data=array(':order_item_id' => $orders->line_items[$i]->id,':meta_key'=>$attributes->name);
										
										$order_attr_res->execute($data);
										
										$earlier_version=1;
										
										if($order_attr_res->rowCount()==0)
										{
												$sql= "Select * from ".$wpdb->prefix."woocommerce_order_itemmeta where order_item_id = :order_item_id and meta_key like 'pa_%'";
												$order_attr_res = $db_pdo->prepare($sql);
												$data=array(':order_item_id' => $orders->line_items[$i]->id);
												$order_attr_res->execute($data);
												$earlier_version=0;
										}
										
										foreach( $order_attr_res as $order_attr )
										{
											$attr_label="";
											if($earlier_version==0 && strstr($order_attr['meta_key'],'pa_'))
											{
												$attr_label=substr($order_attr['meta_key'],3);
											}
											else
											$attr_label=$order_attr['meta_key'];
											
											if($attributes_string!="")
											$attributes_string=$attributes_string.",".$attr_label.":".$order_attr['meta_value'];
											else
											$attributes_string="~".$attr_label.":".$order_attr['meta_value'];
										}
								
									if($earlier_version==0)
									break;
								}
							  }
						}
						
						if($orders->line_items[$actual_number_of_products]->name!="")
						{	
							if(isset($additional_product_arr->dimensions))
							{
								if($additional_product_arr->dimensions->length!="" && $this->woo_orders[$counter]->order_info['PkgLength']=="")
								{
														
								$dim_unit=convert_dim_unit($additional_product_arr->dimensions->unit);
								$this->woo_orders[$counter]->order_info['PkgLength']=$additional_product_arr->dimensions->length;
								$this->woo_orders[$counter]->order_info["PkgWidth"]=$additional_product_arr->dimensions->width;
								$this->woo_orders[$counter]->order_info["PkgHeight"]=$additional_product_arr->dimensions->height;
								}
							}	
											
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Name"]=$orders->line_items[$i]->name.$attributes_string;
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Price"]=number_format($orders->line_items[$i]->price,2,'.','');
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["ExternalID"]=$orders->line_items[$i]->sku;
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]=number_format($orders->line_items[$i]->quantity,2,'.','');
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Total"]=number_format(($orders->line_items[$i]->price*$orders->line_items[$i]->quantity),2,'.','');
							
							$product_weight="";
							if(isset($additional_product_arr->weight))
							$product_weight=$additional_product_arr->weight;
							
							$total_weight_with_unit=ConvertToAcceptedUnit(($product_weight*$orders->line_items[$i]->quantity),strtolower($product_weight_unit));
							$total_weight_with_unit=explode("~",$total_weight_with_unit);
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Total_Product_Weight"]=number_format($total_weight_with_unit[0],2,'.','');
							$uom_weight=$total_weight_with_unit[1];
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Notes"]="";
							$actual_number_of_products++;						
						}
						
					  }
					$this->woo_orders[$counter]->num_of_products=$actual_number_of_products;
					$this->woo_orders[$counter]->order_info["UnitsOfMeasureWeight"]=$uom_weight;
					if($dim_unit!="")
					$this->woo_orders[$counter]->order_info["UnitsOfMeasureLinear"]=$dim_unit;
					
					$counter++;
				}
				
			}
			
			################################### Function GetOrdersByDate($datefrom,$dateto) ######################
			//Get orders based on date range
			#######################################################################################################
			function GetOrdersByDate($datefrom,$dateto)
			{
					
					$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
					
		
					if (isset($this->woo_orders))
						return $this->woo_orders;
					else
									return array();  
					
			}
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
					return "";
				}
				
			}
					
			
		}
}
else
{		
			//Use DB integration
				
			if($port_number!="")
			$db_pdo=new PDO("mysql:host=".$updated_host.";port=".$port_number.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
			else
			$db_pdo=new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);

		############################################## Class ShippingZWoo ######################################
		class ShippingZWoo extends ShippingZGenericShoppingCart
		{
			
			//cart specific functions goes here
			############################################## Function Check_DB_Access #################################
			//Check Database access
			#######################################################################################################
			
			
			
			function Check_DB_Access()
			{
				global $db_pdo,$wpdb;
						
				$sql= "Select * from ".$wpdb->prefix."term_taxonomy where taxonomy = :taxonomy";
				
				$order_status = $db_pdo->prepare($sql);
				
				$data=array(':taxonomy' => 'shop_order_status');
				
				$order_status->execute($data);
				
				if ($order_status->rowCount()) 
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
				
				global $db_pdo,$wpdb;
						
				//Get order count based on data range
				$order_status_filter=$this->PrepareWooOrderStatusFilter();
				
				$sql = "SELECT DISTINCT " . $wpdb->prefix . "postmeta.post_id AS orders FROM " . $wpdb->prefix . "postmeta LEFT JOIN " . $wpdb->prefix . "posts ON (" . $wpdb->prefix . "postmeta.post_id = " . $wpdb->prefix . "posts.ID) LEFT JOIN " . $wpdb->prefix . "term_relationships ON (" . $wpdb->prefix . "posts.ID = " . $wpdb->prefix . "term_relationships.object_id) LEFT JOIN " . $wpdb->prefix . "term_taxonomy ON (" . $wpdb->prefix . "term_relationships.term_taxonomy_id = " . $wpdb->prefix . "term_taxonomy.term_taxonomy_id) LEFT JOIN " . $wpdb->prefix . "terms ON (" . $wpdb->prefix . "term_taxonomy.term_id = " . $wpdb->prefix . "terms.term_id) WHERE " . $wpdb->prefix . "posts.post_status = 'publish' AND " . $wpdb->prefix . "term_taxonomy.taxonomy = 'shop_order_status' AND " . $order_status_filter."  DATE_FORMAT(" . $wpdb->prefix . "posts.post_modified, '%Y-%m-%d %T') >= :datefrom  AND DATE_FORMAT(" . $wpdb->prefix . "posts.post_modified, '%Y-%m-%d %T') <= :dateto";
						
				
				$orders = $db_pdo->prepare($sql);
				
				$data=array(':datefrom' => $this->ConvertDateToDbFormat($datefrom) , ':dateto' => $this->ConvertDateToDbFormat($dateto));
				
				$orders->execute($data);
				
				return $orders->rowCount();
			
			}
			############################################## Function UpdateShippingInfo #################################
			//Update order status (At this point REST API only allows to update order status)
			#######################################################################################################
			function UpdateShippingInfo($OrderNumber,$TrackingNumber='',$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
			{
				global $db_pdo,$wpdb;
					
				$OrderNumber=get_actual_order_id($OrderNumber);
					
				$sql = "SELECT *," . $wpdb->prefix . "terms.name as order_status, ".$wpdb->prefix . "terms.term_id as order_status_id FROM " . $wpdb->prefix . "postmeta LEFT JOIN " . $wpdb->prefix . "posts ON (" . $wpdb->prefix . "postmeta.post_id = " . $wpdb->prefix . "posts.ID) LEFT JOIN " . $wpdb->prefix . "term_relationships ON (" . $wpdb->prefix . "posts.ID = " . $wpdb->prefix . "term_relationships.object_id) LEFT JOIN " . $wpdb->prefix . "term_taxonomy ON (" . $wpdb->prefix . "term_relationships.term_taxonomy_id = " . $wpdb->prefix . "term_taxonomy.term_taxonomy_id) LEFT JOIN " . $wpdb->prefix . "terms ON (" . $wpdb->prefix . "term_taxonomy.term_id = " . $wpdb->prefix . "terms.term_id) WHERE " . $wpdb->prefix . "posts.post_status = 'publish' AND " . $wpdb->prefix . "term_taxonomy.taxonomy = 'shop_order_status' and ".$wpdb->prefix . "postmeta.post_id=:order_id";
				
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
					{
						$original_carrier=$Carrier;
						$Carrier=" via ".$Carrier;
					}
					
					if($Service!="")
					$Service=" [".$Service."]";
					
					$TrackingNumberString="";
					if($TrackingNumber!="")
					$TrackingNumberString=", Tracking number $TrackingNumber";
					
						
					foreach( $result as $order )
					{
						$current_order_status=$order['order_status'];
					}
					
					//prepare $comments & save it
					$comments="Shipped on $shipped_on".$Carrier.$Service.$TrackingNumberString;
					
					$woo_order_data = new WC_Order($OrderNumber);
					
					if(WOO_SHIPPED_STATUS_SET_TO_STATUS_3_COMPLETE==1)
					{
									
						$change_order_status="completed";
					}
					else
					{
						 if(strtolower($current_order_status)=="on-hold")
							$change_order_status="pending";
						else if(strtolower($current_order_status)=="pending")
							$change_order_status="processing";
						else if(strtolower($current_order_status)=="processing")
							$change_order_status="completed";
						else
						$change_order_status=$current_order_status;
											
					}
					
					if(!defined("SHIPMENT_TRACKING_MODULE"))
					define("SHIPMENT_TRACKING_MODULE","0");
					
					// Update tracking information
					if(SHIPMENT_TRACKING_MODULE)
					{
						update_post_meta( $OrderNumber, '_tracking_provider', strtolower($original_carrier));
						update_post_meta( $OrderNumber, '_tracking_number', $TrackingNumber );
						update_post_meta( $OrderNumber, '_date_shipped', time() );
					}
					
					if(WOO_TRACKING_NOTES_UPDATE_ONLY)
					{
						$woo_order_data->add_order_note($comments);
					}
					else
					{
						$woo_order_data->update_status($change_order_status, $comments );
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
			############################################## Function Fetch_DB_Orders #################################
			//Perform Database query & fetch orders based on date range
			#######################################################################################################
			
			function Fetch_DB_Orders($datefrom,$dateto)
			{
				//Get orders based on data range
				global $db_pdo,$wpdb,$product_weight_unit;
						
				//Get order count based on data range
				$order_status_filter=$this->PrepareWooOrderStatusFilter();
				
						
				$sql = "SELECT DISTINCT " . $wpdb->prefix . "postmeta.post_id AS orders FROM " . $wpdb->prefix . "postmeta LEFT JOIN " . $wpdb->prefix . "posts ON (" . $wpdb->prefix . "postmeta.post_id = " . $wpdb->prefix . "posts.ID) LEFT JOIN " . $wpdb->prefix . "term_relationships ON (" . $wpdb->prefix . "posts.ID = " . $wpdb->prefix . "term_relationships.object_id) LEFT JOIN " . $wpdb->prefix . "term_taxonomy ON (" . $wpdb->prefix . "term_relationships.term_taxonomy_id = " . $wpdb->prefix . "term_taxonomy.term_taxonomy_id) LEFT JOIN " . $wpdb->prefix . "terms ON (" . $wpdb->prefix . "term_taxonomy.term_id = " . $wpdb->prefix . "terms.term_id) WHERE " . $wpdb->prefix . "posts.post_status = 'publish' AND " . $wpdb->prefix . "term_taxonomy.taxonomy = 'shop_order_status' AND " . $order_status_filter."  DATE_FORMAT(" . $wpdb->prefix . "posts.post_modified, '%Y-%m-%d %T') >= :datefrom  AND DATE_FORMAT(" . $wpdb->prefix . "posts.post_modified, '%Y-%m-%d %T') <= :dateto";
				
				$orders_result = $db_pdo->prepare($sql);
				
				$data=array(':datefrom' => $this->ConvertDateToDbFormat($datefrom) , ':dateto' => $this->ConvertDateToDbFormat($dateto));

				
				$orders_result->execute($data);
				
				$counter=0;
				$uom_weight="";
				$dim_unit="";
				foreach( $orders_result as $order )
				{
						
						$woo_order_data = new WC_Order($order['orders']);
						$order_id=trim(str_replace('#', '', $woo_order_data->get_order_number()));
													
						//prepare order array
						$this->woo_orders[$counter]=new stdClass();
						$this->woo_orders[$counter]->orderid=$order_id;
						$this->woo_orders[$counter]->order_info['PkgLength']="";
												
						//billing details
						$this->woo_orders[$counter]->order_billing["FirstName"]=$this->Check_Field($woo_order_data,'billing_first_name');
						$this->woo_orders[$counter]->order_billing["LastName"]=$this->Check_Field($woo_order_data,'billing_last_name');
						$this->woo_orders[$counter]->order_billing["Company"]=$this->Check_Field($woo_order_data,'billing_company');
						$this->woo_orders[$counter]->order_billing["Address1"]=$this->Check_Field($woo_order_data,'billing_address_1');
						$this->woo_orders[$counter]->order_billing["Address2"]=$this->Check_Field($woo_order_data,'billing_address_2');
						$this->woo_orders[$counter]->order_billing["City"]=$this->Check_Field($woo_order_data,'billing_city');
						$this->woo_orders[$counter]->order_billing["State"]=$this->Check_Field($woo_order_data,'billing_state');
						$this->woo_orders[$counter]->order_billing["PostalCode"]=$this->Check_Field($woo_order_data,'billing_postcode');
						$this->woo_orders[$counter]->order_billing["Country"]=$this->Check_Field($woo_order_data,'billing_country');
						$this->woo_orders[$counter]->order_billing["Phone"]=$this->Check_Field($woo_order_data,'billing_phone');
						
						//shipping details
						$this->woo_orders[$counter]->order_shipping["FirstName"]=$this->Check_Field($woo_order_data,'shipping_first_name');
						$this->woo_orders[$counter]->order_shipping["LastName"]=$this->Check_Field($woo_order_data,'shipping_last_name');
						$this->woo_orders[$counter]->order_shipping["Company"]=$this->Check_Field($woo_order_data,'shipping_company');
						$this->woo_orders[$counter]->order_shipping["Address1"]=$this->Check_Field($woo_order_data,'shipping_address_1');
						$this->woo_orders[$counter]->order_shipping["Address2"]=$this->Check_Field($woo_order_data,'shipping_address_2');
						$this->woo_orders[$counter]->order_shipping["City"]=$this->Check_Field($woo_order_data,'shipping_city');
						$this->woo_orders[$counter]->order_shipping["State"]=$this->Check_Field($woo_order_data,'shipping_state');
						$this->woo_orders[$counter]->order_shipping["PostalCode"]=$this->Check_Field($woo_order_data,'shipping_postcode');
						$this->woo_orders[$counter]->order_shipping["Country"]=	$this->Check_Field($woo_order_data,'shipping_country');
						$this->woo_orders[$counter]->order_shipping["Phone"]=$this->Check_Field($woo_order_data,'billing_phone');
						$this->woo_orders[$counter]->order_shipping["EMail"]=$this->Check_Field($woo_order_data,'billing_email');
							
												
						//order info
						$order_date_actual = new DateTime($this->Check_Field($woo_order_data,'order_date'));
						$this->woo_orders[$counter]->order_info["OrderDate"]= $order_date_actual->format('Y-m-d\TH:i:00\Z');
						
									
						//$this->woo_orders[$counter]->order_info["ItemsTotal"]=number_format($orders->subtotal,2,'.','');
						$this->woo_orders[$counter]->order_info["Total"]=number_format($this->Check_Field($woo_order_data,'order_total'),2,'.','');
						$this->woo_orders[$counter]->order_info["ItemsTax"]=number_format($this->Check_Field($woo_order_data,'order_tax'),2,'.','');
							
							
						$this->woo_orders[$counter]->order_info["OrderNumber"]=$order_id;
						$this->woo_orders[$counter]->order_info["PaymentType"]=$this->Check_Field($woo_order_data,'payment_method_title');
						$this->woo_orders[$counter]->order_info["ShippingChargesPaid"]=number_format($this->Check_Field($woo_order_data,'order_shipping'),2,'.','');
						$this->woo_orders[$counter]->order_info["ShipMethod"]=$this->Check_Field($woo_order_data,'shipping_method_title');
						$this->woo_orders[$counter]->order_info["Comments"]=$this->Check_Field($woo_order_data,'customer_note');			
			
						$order_status=$this->Check_Field($woo_order_data,'status');
						
						if($order_status!="on-hold" && $order_status!="pending")
							$this->woo_orders[$counter]->order_info["PaymentStatus"]=2;
						else
							$this->woo_orders[$counter]->order_info["PaymentStatus"]=0;
						
						//Show Order status
						if($order_status=="completed")
							$this->woo_orders[$counter]->order_info["IsShipped"]=1;
						else
							$this->woo_orders[$counter]->order_info["IsShipped"]=0;
							
						//show if cancelled
						if($order_status=="cancelled")
							$this->woo_orders[$counter]->order_info["IsCancelled"]=1;
						else
							$this->woo_orders[$counter]->order_info["IsCancelled"]=0;
							
								
						$actual_number_of_products=0;
						
						$woo_order_products_temp=$woo_order_data->get_items();
						
										
						foreach($woo_order_products_temp as $key=>$woo_order_products)
						{
						
						
						if($woo_order_products['name']!="")
						{					
							$product_additional_details=get_product($woo_order_products['product_id']);
												
							$item_meta = new WC_Order_Item_Meta( $woo_order_products['item_meta'] );
							
							if($item_meta->display( true, true )!="")
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Name"]=$woo_order_products['name']."~". $item_meta->display( true, true );
							else
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Name"]=$woo_order_products['name'];
							
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Price"]=number_format($woo_order_products['line_total'],2,'.','');
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["ExternalID"]=$product_additional_details->get_sku();
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]=number_format($woo_order_products['qty'],2,'.','');
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Total"]=number_format(($woo_order_products['line_total']*$woo_order_products['qty']),2,'.','');
							$product_weight=$product_additional_details->get_weight();
							$dimensions = $product_additional_details->get_dimensions();
							if($dimensions!="" && $this->woo_orders[$counter]->order_info['PkgLength']=="")
							{
								
								$dim_temp=explode(" x ",$dimensions);
								$length=trim($dim_temp[0]);
								$width=trim($dim_temp[1]);
								$last_part=trim($dim_temp[2]);
								$last_part_temp=explode(" ",$last_part);
								$height=$last_part_temp[0];
								$unit=$last_part_temp[1];
								
								$this->woo_orders[$counter]->order_info['PkgLength']=$length;
								$this->woo_orders[$counter]->order_info['PkgWidth']=$width;
								$this->woo_orders[$counter]->order_info['PkgHeight']=$height;
								
								$dim_unit=convert_dim_unit($unit);
							}	
							
							$total_weight_with_unit=ConvertToAcceptedUnit(($product_weight*$woo_order_products['qty']),strtolower($product_weight_unit));
							$total_weight_with_unit=explode("~",$total_weight_with_unit);
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Total_Product_Weight"]=number_format($total_weight_with_unit[0],2,'.','');
							$uom_weight=$total_weight_with_unit[1];
							$this->woo_orders[$counter]->order_product[$actual_number_of_products]["Notes"]="";
							$actual_number_of_products++;						
						}
					  }
					$this->woo_orders[$counter]->num_of_products=$actual_number_of_products;
					$this->woo_orders[$counter]->order_info["UnitsOfMeasureWeight"]=$uom_weight;
					if($dim_unit!="")
					$this->woo_orders[$counter]->order_info["UnitsOfMeasureLinear"]=$dim_unit;	
					$counter++;
				}
				
			}
			
			################################### Function GetOrdersByDate($datefrom,$dateto) ######################
			//Get orders based on date range
			#######################################################################################################
			function GetOrdersByDate($datefrom,$dateto)
			{
					
					$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
					
		
					if (isset($this->woo_orders))
						return $this->woo_orders;
					else
									return array();  
					
			}
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
					return "";
				}
				
			}
			############################################## Function ConvertDateToDbFormat #################################
			//"T" & "Z" remove from UTC format(in ISO 8601) 
			#######################################################################################################
			function ConvertDateToDbFormat($server_date_utc)  
			{
				if(strpos($server_date_utc,"Z"))
				{
					$utc_fotmat_temp=str_replace("Z","",$server_date_utc);
					$server_date_utc=str_replace("T","",$utc_fotmat_temp);
				   
				}  
				return $server_date_utc;
			}
			
			################################################ Function PrepareWooOrderStatusFilter #######################
			//Prepare order status string based on settings
			#######################################################################################################
			function PrepareWooOrderStatusFilter()
			{
					global $wpdb;
					
					$order_status_filter="";
					
					if(WOO_RETRIEVE_ORDER_STATUS_0_ON_HOLD==1)
					{
						$order_status_filter=$wpdb->prefix . "terms.name = 'on-hold'";
					
					}
					if(WOO_RETRIEVE_ORDER_STATUS_1_PENDING==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=$wpdb->prefix . "terms.name = 'pending'";
						}
						else
						{
							$order_status_filter.=" OR ".$wpdb->prefix . "terms.name = 'pending'";
						}
					
					}
					if(WOO_RETRIEVE_ORDER_STATUS_2_PROCESSING==1)
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=$wpdb->prefix . "terms.name = 'processing'";
						}
						else
						{
							$order_status_filter.=" OR ".$wpdb->prefix . "terms.name = 'processing'";
						}
					
					}
					
					if(WOO_RETRIEVE_ORDER_STATUS_3_COMPLETE==1 )
					{
						if($order_status_filter=="")
						{
							$order_status_filter.=$wpdb->prefix . "terms.name = 'completed'";
						}
						else
						{
							$order_status_filter.=" OR ".$wpdb->prefix . "terms.name = 'completed'";
						}
					}
					if(WOO_RETRIEVE_ORDER_STATUS_4_CANCELLED==1)
					{
					
						if($order_status_filter=="")
						{
							$order_status_filter.=$wpdb->prefix . "terms.name = 'cancelled'";
						}
						else
						{
							$order_status_filter.=" OR ".$wpdb->prefix . "terms.name = 'cancelled'";
						}
					
					}
					
					if($order_status_filter!="")
					$order_status_filter="( ".$order_status_filter." ) and";
					return $order_status_filter;
					
			}
					
			
		}
}
######################################### End of class ShippingZWoo ###################################################

	// create object & perform tasks based on command

	$obj_shipping_woo=new ShippingZWoo;
	$obj_shipping_woo->ExecuteCommand();

?>