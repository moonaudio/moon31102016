<?php
##############################################################################################################################
//SHIPPINGZ MAGENTO API TEST SCRIPT
############################################### Check & adjust "default_socket_timeout"#######################################
$timeout_value="";
$timeout_value=@ini_get("default_socket_timeout");
if($timeout_value!="" && $timeout_value<120)
@ini_set("default_socket_timeout",120);
############################################## Always Enable Exception Handler ###############################################
error_reporting(E_ALL);
ini_set('display_errors', '1');
############################################### SETTINGS #####################################################################
$domain="localhost";
$dir="magento-1.8.0.0/magento"; //leave blank if not applicable
#   -----------------------------------------------
# For example, on a Magento v1.2 store that resides at www.supergreatstuff.com, the settings would be:
# $domain="supergreatstuff.com"
#
# If your store is located on a sub-folder like "newmagento_v1_2", then "$dir" setting would be
# $dir="newmagento_v1_2"; 
# Otherwise use:
# $dir="";
#
$check_domain=0; //set 0 if domain check not required like for localhost

$apiuser= 'user'; //Magento API User [Please, refer "Magento-Setup-Document.pdf" for details]
$apikey = 'MAGkey';//Magento API Key [Please, refer "Magento-Setup-Document.pdf" for details]

###############################################################################################################################
# This script can be run on any computer -- on the Magento server, or on another machine that has php.

# It may be best to run it from a machine OTHER than the actual Magento server being tested, as there are often DNS issues on the server itself, looking back at itself.

# To run this script, just keep this file at the root folder of magento store and invoke it from browser:

# http://<server where the script resides>/ShippingZMagento_Test_Api.php

# If all is well, you will get an All Tests Passed message.

##############################################################################################################################
##############################################################################################################################
/******************************************* DO NOT CHANGE ANY CODE BELOW THIS *********************************************/
##############################################################################################################################
##############################################################################################################################
if($dir!="")
{
	if(!strstr($domain,'localhost'))
	$host= 'http://www.'.$domain.'/'.$dir;
	else
	$host= 'http://'.$domain.'/'.$dir;
}
else
{
	if(!strstr($domain,'localhost'))
	$host= 'http://www.'.$domain;
	else
	$host= 'http://'.$domain;
}
############################################# Utility Function ##############################################################
function get_allowed_methods($methods_arr)
{	
	$counter=0;
	foreach($methods_arr as $key=>$value)
	{
		$allowed_method_names[$counter]=$value['name'];
		$counter++;
	}
	return $allowed_method_names;
}
##################################################### Domain check #############################################################
function checkDomain($domain,$server,$findText)
{
        // Open a socket connection to the whois server
        $con = fsockopen($server, 43);
        if (!$con) return false;
        
        // Send the requested doman name
        fputs($con, $domain."\r\n");
        
        // Read and store the server response
        $response = ' :';
        while(!feof($con)) {
            $response .= fgets($con,128); 
        }
        
        // Close the connection
        fclose($con);
        
        // Check the response stream whether the domain is available
        if (strpos($response, $findText)){
            return true;
        }
        else {
            return false;   
        }
 }
 
 if($check_domain)
 {
 	echo "Checking Domain Name....<br>";
	if (checkDomain($domain,'whois.crsnic.net','No match for'))
	{
		  echo "Domain $domain accessible<br><br>";
	}
	else echo "Domain $domain not accessible<br><br>";
}
######################################################### DNS lookup ##############################################################
function win_checkdnsrr($host, $type='MX') 
{
    if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') { return; }
    if (empty($host)) { return; }
    $types=array('A', 'MX', 'NS', 'SOA', 'PTR', 'CNAME', 'AAAA', 'A6', 'SRV', 'NAPTR', 'TXT', 'ANY');
    if (!in_array($type,$types)) {
        user_error("checkdnsrr() Type '$type' not supported", E_USER_WARNING);
        return;
    }
    @exec('nslookup -type='.$type.' '.escapeshellcmd($host), $output);
    foreach($output as $line){
        if (preg_match('/^'.$host.'/',$line)) { return true; }
    }
}

// Define
if (!function_exists('checkdnsrr')) {
    function checkdnsrr($host, $type='MX') {
        return win_checkdnsrr($host, $type);
    }
}
echo "Testing Magento API Access...<br><br>";
echo "Checking DNS....<br>";

$result=checkdnsrr($domain);
if ($result)
{
	  echo "DNS records found for $domain<br><br>";
}
else 
echo "DNS records not found for $domain<br><br>";
###################################################### Check if url is accessible #########################################
function EXECUTE_CURL($url)
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	//Additional curl options Following ZF Case 24497
	//To make sure curl works for SSL Server too
	//We don't have access to other servers.Hence using following two curl options is safe for our use.
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	 
	$fp = curl_exec($ch); 
			
	$http_code =curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $http_code."~=~".$fp;

}
echo "Checking URL....". $host. "/index.php/api/soap/?wsdl<br>" ;
//check if the url is proper & we can access wsdl
$curl_result=EXECUTE_CURL($host.'/index.php/api/soap/?wsdl');
$curl_result_temp=explode("~=~",$curl_result);
$fp = $curl_result_temp[1]; 
$http_code =$curl_result_temp[0];
if($http_code==200)
{
	$proxy= new SoapClient($host.'/index.php/api/soap/?wsdl',array('exceptions' => 1,'trace' => 1,"connection_timeout" => 120));
	 
	
	try {
	  $sessionId= $proxy->login($apiuser, $apikey);
	  echo  "Magento Api accessed Successfully.";
	  echo "<br>Session Id is:". $sessionId;
	  $arr_resource=$proxy->resources($sessionId);
	 
	  ################################################################## Check for proper rights ############################################
	  echo  "<br><br>Checking Resource Rights for API User \"$apiuser\".....<br><br>";
	  $resource_right=1;
	  $insufficient_resource_arr=array();
	  $found_resource_arr=array();
	  $resource_counter=0;
	  $resource_found_counter=0;
	  $required_resource_arr=array('customer','customer_group','customer_address','catalog_category','catalog_category_attribute','catalog_product','catalog_product_attribute','catalog_product_type','catalog_product_attribute_tier_price','sales_order','sales_order_shipment','sales_order_invoice');
	  
	  foreach($arr_resource as $key=>$value)
	  {
	  
		
		//Retrieve allowed methods related to Customer API 
		if($value['name']=="customer")
		{
			$found_resource_arr[$resource_found_counter]="customer";
			$resource_found_counter++;
			
			$customer_allowed_method_names=get_allowed_methods($value['methods']);
			if(!in_array("list",$customer_allowed_method_names) || !in_array("info",$customer_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Customer";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to Customer Group API 
		if($value['name']=="customer_group")
		{
			$found_resource_arr[$resource_found_counter]="customer_group";
			$resource_found_counter++;
			
			$customer_allowed_method_names=get_allowed_methods($value['methods']);
			
			if(!in_array("list",$customer_allowed_method_names) )
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Customer Group";
				 $resource_counter++;
			}
		}
		
		//Retrieve allowed methods related to Customer Address API 
		if($value['name']=="customer_address")
		{
			$found_resource_arr[$resource_found_counter]="customer_address";
			$resource_found_counter++;
			
			$customer_allowed_method_names=get_allowed_methods($value['methods']);
			if(!in_array("list",$customer_allowed_method_names) || !in_array("info",$customer_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Customer Address";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to Catalog Category API
		if($value['name']=="catalog_category")
		{
			$found_resource_arr[$resource_found_counter]="catalog_category";
			$resource_found_counter++;
						
			$resource_allowed_method_names=get_allowed_methods($value['methods']);
			
			if(!in_array("tree",$resource_allowed_method_names) )
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Catalog Category";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to Catalog Category Attribute API
		if($value['name']=="catalog_category_attribute")
		{
			$found_resource_arr[$resource_found_counter]="catalog_category_attribute";
			$resource_found_counter++;
						
			$resource_allowed_method_names=get_allowed_methods($value['methods']);
			if(!in_array("list",$resource_allowed_method_names) || !in_array("options",$resource_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Catalog Category Attribute";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to Catalog Product Attribute API
		if($value['name']=="catalog_product")
		{
			$found_resource_arr[$resource_found_counter]="catalog_product";
			$resource_found_counter++;
						
			$resource_allowed_method_names=get_allowed_methods($value['methods']); 
			if(!in_array("list",$resource_allowed_method_names) || !in_array("info",$resource_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Catalog Product";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to Catalog Product Attribute API
		if($value['name']=="catalog_product_attribute")
		{
			$found_resource_arr[$resource_found_counter]="catalog_product_attribute";
			$resource_found_counter++;
						
			$resource_allowed_method_names=get_allowed_methods($value['methods']); 
			if(!in_array("info",$resource_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Catalog Product Attribute";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to Catalog Product Type API
		if($value['name']=="catalog_product_type")
		{
			$found_resource_arr[$resource_found_counter]="catalog_product_type";
			$resource_found_counter++;
						
			$resource_allowed_method_names=get_allowed_methods($value['methods']); 
			if(!in_array("list",$resource_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Catalog Product Type";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to Catalog Product Type API
		if($value['name']=="catalog_product_attribute_tier_price")
		{
			$found_resource_arr[$resource_found_counter]="catalog_product_attribute_tier_price";
			$resource_found_counter++;
						
			$resource_allowed_method_names=get_allowed_methods($value['methods']); 
			if(!in_array("info",$resource_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Catalog Product Atrribute Tier Price";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to ORDER API 
		if($value['name']=="sales_order")
		{
			$found_resource_arr[$resource_found_counter]="sales_order";
			$resource_found_counter++;
			
			$order_allowed_method_names=get_allowed_methods($value['methods']);
			if(!in_array("list",$order_allowed_method_names) || !in_array("info",$order_allowed_method_names)|| !in_array("addComment",$order_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Sales Order";
				 $resource_counter++;
			}
		}
		
		//Retrieve allowed methods related to Shipment API 
		if($value['name']=="sales_order_shipment")
		{
			$found_resource_arr[$resource_found_counter]="sales_order_shipment";
			$resource_found_counter++;
			
			$shipment_allowed_method_names=get_allowed_methods($value['methods']);
			if(!in_array("list",$shipment_allowed_method_names) || !in_array("info",$shipment_allowed_method_names)|| !in_array("addComment",$shipment_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Order Shipment";
				 $resource_counter++;
			}
		}
		//Retrieve allowed methods related to Sales Order Invoice API
		if($value['name']=="sales_order_invoice")
		{
			$found_resource_arr[$resource_found_counter]="sales_order_invoice";
			$resource_found_counter++;
						
			$resource_allowed_method_names=get_allowed_methods($value['methods']);
			if(!in_array("list",$resource_allowed_method_names) || !in_array("info",$resource_allowed_method_names))
			{
				 $resource_right=0;
				 $insufficient_resource_arr[$resource_counter]="Sales Order Invoice";
				 $resource_counter++;
			}
		}
		
	  }
	  
	  
	  $arr_diff=array_diff($required_resource_arr, $found_resource_arr);
	 
	 	if(count($arr_diff)!=0)
		{
			 echo "API User  \"$apiuser\" does not have access to following resources:<br>";
			foreach($arr_diff as $key=>$val)
			{
				echo $val."<br>";
			}
			echo "<br>";
	   }
	 
	  if($resource_right && count($arr_diff)==0)
	  {
		echo "API User \"$apiuser\" has proper rights to access required resources.";
	  }
	   else
	  {
	  	if(count($insufficient_resource_arr)!=0)
		{
			echo "API User  \"$apiuser\" does not have proper rights to access following resources:<br>";
			foreach($insufficient_resource_arr as $key=>$val)
			{
				echo $val."<br>";
			}
		}
		
	  }
	  
	 
	 if(count($arr_diff)!=0 || !$resource_right)
	{
		echo "<br>Please, refer \"Magento-Setup-Document.pdf\" and make sure that API user has required rights.";
	}
	  
	} catch (Exception $e) {
	  echo "==> Error: ".$e->getMessage();
	  exit();
	} 	
}
else
{
	echo "Can not aceess wsdl url <".$host."/index.php/api/soap/?wsdl>";

}

echo "<br><br>#################################################################<br><br>Testing MAGE model...<br><br>";

############################################### Function PrepareOrderStatusString #######################
	//Prepare order status string based on settings
	#######################################################################################################
	function PrepareMagentoOrderStatusFilter()
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
			return $order_status_filter;
			
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
	function GetProductOptions($option_string,$code='')
	{
			
			$option_arr=SafeUnserialize($option_string);
			
			if($code=="")
			{
				//get attribute details
				$formatted_option_variation_details="";
							
				if(isset($option_arr['attributes_info']))
				{
					foreach($option_arr['attributes_info'] as $key=>$val)
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
									$formatted_option_variation_details.=", ".$value2;
								else
									$formatted_option_variation_details=$value2;
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
###########################################################################################
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
if(Check_Include_File("app/Mage.php"))
include('app/Mage.php');

######################################### Function check if required fieldnames are present or not #######################################################
function Check_Field($field,$field_display)
{
	if(isset($field))
	{
		echo "<font style=\"color:green\"><strong>$$field_display</strong> available</font><br>";
		return $field;
	}
	else
	{
		echo "<font style=\"color:red\"><strong>$$field_display</strong> not available</font><br>";
		return "NA";
	}
	
}
#################################################### End of function#########################################################################
$app = Mage::app();
//set developer mode
$_SERVER['MAGE_IS_DEVELOPER_MODE']=true;

//Display version info
echo "Magento Version:".Mage::getVersion()."<br>";
echo "PHP version:".phpversion()."<br><br>";

echo "Checking access to required models<br>";

try
{
	$orders_data = Mage::getModel('sales/order')->getCollection();
	
	  echo "==> Success accessing order model"."<br>";
}
catch(Exception $e) 
{
	  echo "==> Error acessing order model: ".$e->getMessage()."<br>";
	  exit();
} 	
try
{
	$message = Mage::getModel('giftmessage/message');
	 echo "==> Success accessing giftmessage model"."<br>";
}
catch(Exception $e) 
{
	  echo "==> Error acessing gift message model: ".$e->getMessage()."<br>";
	  exit();
} 
echo "<br>Checking order data and different fields/properties............";

$order_counter=1;

$orders_data = Mage::getModel('sales/order')->getCollection();
        	$orders_data->addAttributeToSelect("*")
            ->getSelect();
			
             $orders_data->setPageSize($order_counter)
			   ->setCurPagE(1)
			   ->loadData();
			   
		$magento_orders=array();
		$counter=0;
		echo "<br><br>DUMPING MAGENTO ORDER DATA:<br><br>";
		var_dump($orders_data);
		echo "<br>###########################################################  End of dump order data  ####################################################<br>";
		foreach ($orders_data as $order) 
		{
			
			$order_id=$order->getIncrementId();
			
				
			//prepare order array
			$magento_orders[$counter]=new stdClass();
			$magento_orders[$counter]->orderid=$order_id;
			
			
			//shipping details
			$ShippingAddress=$order->getShippingAddress();
			echo "Checking getShippingAddress() & related shipping fields:<br><br>";
			$magento_orders[$counter]->order_shipping["EMail"]=$order->getCustomerEmail();
			if(is_object($ShippingAddress))
			{		
				
				$magento_orders[$counter]->order_shipping["FirstName"]=Check_Field($ShippingAddress->firstname,"ShippingAddress->firstname");
				$magento_orders[$counter]->order_shipping["LastName"]=Check_Field($ShippingAddress->lastname,"ShippingAddress->lastname");
				$magento_orders[$counter]->order_shipping["Company"]=Check_Field($ShippingAddress->getCompany(),"ShippingAddress->getCompany()");
				$magento_orders[$counter]->order_shipping["Address1"]=Check_Field($ShippingAddress->getStreet(1),"ShippingAddress->getStreet(1)");
				$magento_orders[$counter]->order_shipping["Address2"]=Check_Field($ShippingAddress->getStreet(2),"ShippingAddress->getStreet(2)");
				$magento_orders[$counter]->order_shipping["City"]=Check_Field($ShippingAddress->getCity(),"ShippingAddress->getCity()");
				$magento_orders[$counter]->order_shipping["State"]=Check_Field($ShippingAddress->getRegionCode(),"ShippingAddress->getRegionCode()");
				$magento_orders[$counter]->order_shipping["PostalCode"]=Check_Field($ShippingAddress->getPostcode(),"ShippingAddress->getPostcode()");
				$magento_orders[$counter]->order_shipping["Country"]=Check_Field($ShippingAddress->getCountryId(),"ShippingAddress->getCountryId()");
				$magento_orders[$counter]->order_shipping["Phone"]=Check_Field($ShippingAddress->getTelephone(),"ShippingAddress->getTelephone()");
				
				
			}
			else
			{
				echo "\"getShippingAddress()\" did not return object<br>";	
			}
			//billing details
			echo "<br>Checking getBillingAddress() & related billing fields:<br><br>";
			$BillingAddress = $order->getBillingAddress();
			if(is_object($BillingAddress))
			{
			$magento_orders[$counter]->order_billing["FirstName"]=Check_Field($BillingAddress->firstname,"BillingAddress->firstname");
			$magento_orders[$counter]->order_billing["LastName"]=Check_Field($BillingAddress->lastname,"BillingAddress->lastname");
			$magento_orders[$counter]->order_billing["Company"]=Check_Field($BillingAddress->getCompany(),"BillingAddress->getCompany()");
			$magento_orders[$counter]->order_billing["Address1"]=Check_Field($BillingAddress->getStreet(1),"BillingAddress->getStreet(1)");
			$magento_orders[$counter]->order_billing["Address2"]=Check_Field($BillingAddress->getStreet(2),"BillingAddress->getStreet(2)");
			$magento_orders[$counter]->order_billing["City"]=Check_Field($BillingAddress->getCity(),"BillingAddress->getCity()");
			$magento_orders[$counter]->order_billing["State"]=Check_Field($BillingAddress->getRegionCode(),"BillingAddress->getRegionCode()");
			$magento_orders[$counter]->order_billing["PostalCode"]=Check_Field($BillingAddress->getPostcode(),"BillingAddress->getPostcode()");
			$magento_orders[$counter]->order_billing["Country"]=Check_Field($BillingAddress->getCountryId(),"BillingAddress->getCountryId()");
			$magento_orders[$counter]->order_billing["Phone"]=Check_Field($BillingAddress->getTelephone(),"BillingAddress->getTelephone()");
			}
			else
			{
				echo "\"getBillingAddress()\" did not return object<br>";	
			}
			//order info
			$magento_orders[$counter]->order_info["OrderDate"]=ConvertServerTimeToUTCMagento($order->getCreatedAt());
			
			if(MAGENTO_READ_INVOICES)
			$magento_orders[$counter]->order_info["ExternalID"]=$invoice_str;
			
			$magento_orders[$counter]->order_info["ItemsTotal"]=number_format($order->getSubtotal(),2,'.','');
			$magento_orders[$counter]->order_info["Total"]=number_format($order->getGrandTotal(),2,'.','');
			if($order->getTaxAmount()!="")
			{
				$magento_orders[$counter]->order_info["ItemsTax"]=number_format($order->getTaxAmount(),2,'.','');
			}
			else
			{
				$magento_orders[$counter]->order_info["ItemsTax"]=0.00;
			}
			$magento_orders[$counter]->order_info["OrderNumber"]=$order_id;
			
			//Get Payment details
			$payment=$order->getPayment();
			$magento_orders[$counter]->order_info["PaymentType"]=Mage::helper('payment')->getMethodInstance($payment->getMethod())->getTitle();
			$magento_orders[$counter]->order_info["ShippingChargesPaid"]=number_format($order->getShippingAmount(),2,'.','');
			$magento_orders[$counter]->order_info["ShipMethod"]=$order->getShippingDescription();
			$magento_orders[$counter]->order_info["Comments"]="";			

			if($order->getStatus()!="pending")
				$magento_orders[$counter]->order_info["PaymentStatus"]=2;
			else
				$magento_orders[$counter]->order_info["PaymentStatus"]=0;
			
			//Show Order status
			if($order->getStatus()=="complete")
				$magento_orders[$counter]->order_info["IsShipped"]=1;
			else
				$magento_orders[$counter]->order_info["IsShipped"]=0;
				
			//show if cancelled
			if($order->getStatus()=="canceled")
				$magento_orders[$counter]->order_info["IsCancelled"]=1;
			else
				$magento_orders[$counter]->order_info["IsCancelled"]=0;
				
				
			 //handle closed order
			if($order->getStatus()=="closed")
			{
				$magento_orders[$counter]->order_info["IsCancelled"]=1;
				$magento_orders[$counter]->order_info["PaymentStatus"]=0;
				$magento_orders[$counter]->order_info["IsShipped"]=0;
			}
			
			//Order Level Gift Message
			if(Magento_RetrieveOrderGiftMessage==1)
			{
				$message = Mage::getModel('giftmessage/message');
				$gift_message_id = $order->getGiftMessageId();
				
				if(!is_null($gift_message_id)) 
				{
						$message->load((int)$gift_message_id);
						$magento_orders[$counter]->order_info["Comments"]=$GetGiftMessageText($message);
				}
			}
			
			
			
			//Get order products
			$actual_number_of_products=0;
			
			
			$order_items=$order->getAllItems();
			
			
			foreach ($order_items as $item)
			{
				if($item->getParentItemId()=="")
				{
									
				
				$magento_orders[$counter]->order_product[$actual_number_of_products]["Name"]=$item->getName().GetProductOptions(serialize($item->getProductOptions()));
							
				
				 if (version_compare(Mage::getVersion(), '1.3.0', '>='))
				{
					$magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getPrice();
				}
				else
				{
					if ($item->hasOriginalPrice())
					{
						$magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getOriginalPrice();
					}
					elseif ($item->hasCustomPrice())
					{
						$magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]=$item->getCustomPrice();
					}
					
				}
				
				$magento_orders[$counter]->order_product[$actual_number_of_products]["ExternalID"]=$item->getSku().GetProductOptions($item->getProductOptionByCode('simple_sku'));
				$magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]=number_format($item->getQtyOrdered(),2,'.','');;
				$magento_orders[$counter]->order_product[$actual_number_of_products]["Total"]=number_format(($magento_orders[$counter]->order_product[$actual_number_of_products]["Price"]*$magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]),2,'.','');
				$magento_orders[$counter]->order_product[$actual_number_of_products]["Total_Product_Weight"]=number_format(($item->getWeight()*$magento_orders[$counter]->order_product[$actual_number_of_products]["Quantity"]),2,'.','');
				
				$magento_orders[$counter]->order_product[$actual_number_of_products]["Notes"]="";
				
				//Product Level Gift Message
				if(Magento_RetrieveProductGiftMessage==1)
				{
					$gift_message_id = $item->getGiftMessageId();
					
					if(!is_null($gift_message_id)) 
					{
							$message->load((int)$gift_message_id);
							$magento_orders[$counter]->order_product[$actual_number_of_products]["Notes"]=$message->getData('message');
					}
				
				}
				
				$actual_number_of_products++;
				
				}
			}
			
			$magento_orders[$counter]->num_of_products=$actual_number_of_products;
			
			
			
			$counter++;
		}	
		
		echo "<br><br>DISPLAY SHIPPINGZ ORDER DATA:<br><br>";
		print_r($magento_orders);
		echo "<br>###########################################################  End of shippingz order data  ####################################################<br>";

?>