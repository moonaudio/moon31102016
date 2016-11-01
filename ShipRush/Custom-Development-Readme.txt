How To Use ShipRush & My.ShipRush with Custom Apps based on PHP

ShipRush comes out of the box ready to work with carts like Magento, Zencart, and others.

Any php developer can easily use ShipRush with custom systems (and stock systems not supported by Z-Firm) 
by writing 100 to 400 lines of php. (Z-Firm's integrations are all under 400 lines at this writing.)

The architecture:

  (1) ShipRush Order Manager -> (2) My.ShipRush -> (3) Your PHP Application
  
Much of the work can be done with just components 2 and 3. However, to actually process shipments and post 
tracking information into your application, you will need to install the ShipRush Order Manager. We suggest
using ShipRush USPS for testing, as it is free of charge and requires no shipping account to use.


Step 1: Establish Connectivity from My.ShipRush to your web store: Simulation

    In this section, you will install the ShipRush "simulator" php module on your web server.
    This is stand alone code that generates sample orders. 
    This step is important, because it connects the My.ShipRush system to your web server, and establishes
    a data connection.
    This step should take under fifteen minutes to set up.

    Steps:
		Go to http://my.shiprush.com
		Create a new account if you do not have one
		Go into Web Stores, select Add New Web Store
		Select "Custom" as the web store type
    Review the steps in "Step 1a" below
		Follow the steps in the wizard.
	
	At this point, the My.ShipRush "Shipping" area should show a one or more orders that have flowed
	from the simulator code.
	
Step 1a: Get the simulator working

    - Modify ShippingZSettings.php
      - Locate SHIPPING_ACCESS_TOKEN
      - Put in your own token value (can the a simple value for testing)

    - Modify ShippingZClasses.php
      - Remark out line 887 as that is causing an issue (will report to development to fix)
      - Line 887 looks like this: $shipping_order->order_info["IsShipped"]=$cart_order_array->order_info["IsShipped"];

    Upload 4 files to your host: ShippingZSettings.php, ShippingZClasses.php, ShippingZMessages.php, and ShippingZSimulator.php

    Now create the webstore in My.ShipRush:
    - Select Custom
    - For your store URL, it will look like this: http://yourstore.com/subfolderyouputthisin/ShippingZSimulator.php
    - Enter the token that you created above in ShippingZSettings.php

    Do not proceed until you have the simulator working properly!
    
Step 2: Plan Your Code
	
	Review the code of both ShippingZSimulator.php and ShippingZZencart.php. Notice how they function.
	You should implement code for the following functions:

	   Check_DB_Access()
	   GetOrderCountByDate($datefrom,$dateto)
	   GetOrdersByDate($datefrom,$dateto)
	   UpdateShippingInfo() This is recommended, but not required. This is how ShipRush posts tracking numbers into your system.
	
Step 3: Write Your Code

	You will want to create a new module, such as MyCustomShippingModule.php that is based on 
	ShippingZSimulator.php and ShippingZZencart.php. 
	
Step 4: Configure Your Module in My.ShipRush

	Go through the steps to add a Custom web store.
	For the URL, enter the full url to your new module. E.g.	
	
		http://mywidgetstore.com/MyCustomShippingModule.php 

Step 5: Test Your Module

	It can be best to use ShipRush USPS, which can be downloaded from www.shiprush.com/usps
	Enter a serial number for use with Zencart (which also allows custom integration).
	
	Set up Order Manager, add a new Web Store, and go through the configuration.
	Configure the connection to your custom module.
	
	Test order retrieval
	Test the posting of tracking numbers back into your system.
	
  You now have integrated, bidirectional shipping!

  ---

  WARNING: Your implementation of GetOrdersByDate($datefrom,$dateto) MUST respect
  the datefrom and dateto range. This range is the date range of your Order.Modifiedat time stamp.
  
  Explanation: An order is placed at a certain time. That is the "Order.DateTime"  However, on your system,
  orders may change after they are placed. For example, they may move from "not paid" to "paid" or have some
  other attribute that changes after "Order.DateTime" Your system should have another datetime that is
  updated every time an important order attribute changes. This is what we call the "Order.ModifiedAt"

  The date range for GetOrdersByDate should drive from "Order.ModifiedAt" in your system.
  
  Note that My.ShipRush records "Order.DateTime" but does NOT store "Order.ModifiedAt"
  "Order.ModifiedAt" is only on your system.
  
  Note that it is important for Order.DateTime to remain FIXED for a given order #.
  

  
  




##########################################################################################################
################################################################################
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