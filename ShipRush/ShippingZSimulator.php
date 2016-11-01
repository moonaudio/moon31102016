<?php

define("SHIPPINGZSIMULATOR_VERSION","3.0.7.8833");

###################### Please See the Custom-Development-Readme.txt #######################################
#
# The file Custom-Development-Readme.txt has the steps for using this module
#
#######################################################################################################

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

include("ShippingZSettings.php");
include("ShippingZClasses.php");
include("ShippingZMessages.php");

	
//Always turn ON error reporting. 
error_reporting(E_ALL);
ini_set('display_errors', '1');


###################### Settings for webstore simulator #######################################
# For custom developers only. Cart users please IGNORE this section
#######################################################################################################
define("FAKE_ORDER_MODE","1"); //1=>Random orders & 2=>Static
define("NUMBER_OF_ORDERS_PER_DAY","2");//Number of fake orders per day in case of Random orders
define("MAX_RANDOM_ORDERS","2400");//Maximum number of random orders
define("STATIC_XML_ORDER_PATH","orders/orders.xml"); //Define path for XML order file
###########################################################################################################

############################################## Class ShippingZSimulator ######################################
class ShippingZSimulator extends ShippingZGenericShoppingCart
{
	
	//cart specific functions goes here
	
	############################################## Function Check_DB_Access #################################
	//Check Database access(for simulator, we don't need database access.)
	#######################################################################################################
	
	function Check_DB_Access()
	{
		$this->display_msg=DB_SUCCESS_MSG;
	}
	
	############################################## Function UpdateShippingInfo #################################
	//Update order status will always return success
	#######################################################################################################
	function UpdateShippingInfo($OrderNumber,$TrackingNumber,$ShipDate='',$ShipmentType='',$Notes='',$Carrier='',$Service='',$ShippingCost='')
	{
		$this->SetXmlMessageResponse($this->wrap_to_xml('UpdateMessage',"Success"));
	}
	############################################## Function Generate_Random_Code #################################
	//Generates random numbers
	#######################################################################################################
	
	function Generate_Random_Code()
	{		
		 	$random_code="";
			$number="ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
			for($i=0;$i<8;$i++)
			{
				$new_number=rand(0,strlen($number)-1);
				$random_code.=$number{$new_number} ;
				
			}
			return $random_code;
	}
	############################################## Function Date_Difference_in_Days #################################
	//Returns full number of full days between two dates
	#######################################################################################################
	function Date_Difference_in_Days($datefrom, $dateto, $using_timestamps = false) 
	{
	  if(!$using_timestamps) 
	  {
		$datefrom = strtotime($datefrom, 0);
		$dateto = strtotime($dateto, 0);
	  }
	  $difference = $dateto - $datefrom; // Difference in seconds
	   $datediff = floor($difference / 86400);
 		   
 	   return $datediff;
     }
	############################################## Function Fetch_DB_Orders #################################
	# Generate orders based on date range 
  #
  # ** Please see the note on GetOrdersByDate function below. **
  # 
	#######################################################################################################
	
	function Fetch_DB_Orders($datefrom,$dateto)
	{
		if(FAKE_ORDER_MODE==1)
		{
				
				$number_of_days=$this->Date_Difference_in_Days($datefrom,$dateto);
				$total_random_orders=NUMBER_OF_ORDERS_PER_DAY*$number_of_days;
				
				//Make sure that total number of random orders does not exceed max settings
				if($total_random_orders>MAX_RANDOM_ORDERS)
				$total_random_orders=MAX_RANDOM_ORDERS;
				
				$this->simulator_orders=array();
				
				//generate random orders
				for($counter=0;$counter<$total_random_orders;$counter++) 
				{
			
				$order_id=($counter+1);
				
				//generate random code for each order 
				$this->random_code=$this->Generate_Random_Code().$order_id;
				
				
				
				//prepare order array
				$this->simulator_orders[$counter]->orderid=$order_id;
				
				//shipping details
				$this->simulator_orders[$counter]->order_shipping["FirstName"]="John ".$this->random_code;
				$this->simulator_orders[$counter]->order_shipping["LastName"]="Henry ".$this->random_code;
				$this->simulator_orders[$counter]->order_shipping["Company"]="Z-Firm LLC ".$this->random_code;
				$this->simulator_orders[$counter]->order_shipping["Address1"]="120 Lakeside Ave ".$this->random_code;
				$this->simulator_orders[$counter]->order_shipping["City"]="Seattle ".$this->random_code;
				$this->simulator_orders[$counter]->order_shipping["State"]="WA";
				$this->simulator_orders[$counter]->order_shipping["PostalCode"]="98122";
				$this->simulator_orders[$counter]->order_shipping["Country"]="US";
				$this->simulator_orders[$counter]->order_shipping["Phone"]="812-7874";
				$this->simulator_orders[$counter]->order_shipping["EMail"]="ABCTEST".$this->random_code."@zfirmllc.com";
					
				//billing details
				$this->simulator_orders[$counter]->order_billing["FirstName"]="John ".$this->random_code;
				$this->simulator_orders[$counter]->order_billing["LastName"]="Henry ".$this->random_code;
				$this->simulator_orders[$counter]->order_billing["Company"]="Z-Firm LLC ".$this->random_code;
				$this->simulator_orders[$counter]->order_billing["Address1"]="120 Lakeside Ave ".$this->random_code;
				$this->simulator_orders[$counter]->order_billing["City"]="Seattle ".$this->random_code;
				$this->simulator_orders[$counter]->order_billing["State"]="WA";
				$this->simulator_orders[$counter]->order_billing["PostalCode"]="98122";
				$this->simulator_orders[$counter]->order_billing["Country"]="US";
				$this->simulator_orders[$counter]->order_billing["Phone"]="812-7874";
				$this->simulator_orders[$counter]->order_billing["EMail"]="ABCTEST".$this->random_code."@zfirmllc.com";
				
				//order info
				$this->simulator_orders[$counter]->order_info["OrderDate"]=date("Y-m-d");
				$this->simulator_orders[$counter]->order_info["ItemsTotal"]="500";
				$this->simulator_orders[$counter]->order_info["Total"]="565";
				$this->simulator_orders[$counter]->order_info["ItemsTax"]="50";
				$this->simulator_orders[$counter]->order_info["OrderNumber"]=$order_id." ".$this->random_code;
				$this->simulator_orders[$counter]->order_info["PaymentType"]="Paypal";
				$this->simulator_orders[$counter]->order_info["IsShipped"]=0; 
				$this->simulator_orders[$counter]->order_info["ShippingChargesPaid"]="15";
				$this->simulator_orders[$counter]->order_info["ShipMethod"]="Flat Rate - Fixed";
				$this->simulator_orders[$counter]->order_info["Comments"]="";
				$this->simulator_orders[$counter]->order_info["PaymentStatus"]=2;
				
				
				//Get order products
				$actual_number_of_products=0;
				for($i=0;$i<2;$i++)
				{
					
					$this->simulator_orders[$counter]->order_product[$i]["Name"]="ShippingZ Plugins_$i";
					$this->simulator_orders[$counter]->order_product[$i]["Price"]=125;
					$this->simulator_orders[$counter]->order_product[$i]["Quantity"]=2;
					$this->simulator_orders[$counter]->order_product[$i]["Total"]=$this->simulator_orders[$counter]->order_product[$i]["Price"]*$this->simulator_orders[$counter]->order_product[$i]["Quantity"];
					$this->simulator_orders[$counter]->order_product[$i]["ExternalID"]="124".$this->random_code;
					$actual_number_of_products++;
					
				}
				
				$this->simulator_orders[$counter]->num_of_products=$actual_number_of_products;
				
				
				
			   }		
		}
		else
		{
			//Get orders from XML file located at the specified path
		
			$url="http".(((empty($_SERVER['HTTPS'])&&$_SERVER['SERVER_PORT']!=443))?"" : "s")."://".$_SERVER['HTTP_HOST']."/".STATIC_XML_ORDER_PATH;
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$order_data = curl_exec($ch); 
			
			$this->Display_XML_Output($order_data);
			
		
		}
		
	}
	
	################################### Function GetOrdersByDate($datefrom,$dateto) ######################
	# Get orders based on date range
  #
  # NOTE that your code MUST respect the date range passed in via ($datefrom,$dateto)
  #
  # WARNING: Your implementation of GetOrdersByDate($datefrom,$dateto) MUST respect
  # the datefrom and dateto range. This range is the date range of your Order.Modifiedat time stamp.
  #
  # Explanation: An order is placed at a certain time. Let's call this the "Order.DateTime"  However, on your system,
  # orders may change after they are placed. For example, they may move from "not paid" to "paid" or have some
  # other attribute that changes after "Order.DateTime" Your system should have another datetime that is
  # updated every time an important order attribute changes. This is what we call the "Order.ModifiedAt"
  # 
  # The date range for GetOrdersByDate should drive from "Order.ModifiedAt" in your system.
  # 
  # Note that My.ShipRush records "Order.DateTime" but does NOT store "Order.ModifiedAt"
  # "Order.ModifiedAt" is only on your system.
  # 
  # Note that it is important for Order.DateTime to remain FIXED for a given order #.
  # 
  #######################################################################################################
	function GetOrdersByDate($datefrom,$dateto)
	{
			
			$this->Fetch_DB_Orders($this->DateFrom,$this->DateTo);
			
			if (isset($this->simulator_orders))
				return $this->simulator_orders;
			else
                       		return array();  

		
	}
	
	
	
	
	
}
######################################### End of class ShippingZSimulator ###################################################

	//create ShippingZSimulator object & perform tasks based on command
	$obj_shipping_simulator=new ShippingZSimulator;
	$obj_shipping_simulator->ExecuteCommand();	

?>