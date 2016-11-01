<?php

# (c) 2009-2014 Z-Firm LLC  ALL RIGHTS RESERVED
# FULL COPYRIGHT NOTICE AND TERMS OF USE ARE AT THE BOTTOM OF THIS DOCUMENT.

define("SHIPPINGZSETTINGS_VERSION","3.0.7.8833");

############################################# Please Read These Instructions #######################################
#
#   Your Attention Please !
#
#
#   ShipRush WILL NOT FUNCTION until you take these steps!
#
#   Step 1: Create & configure a random SHIPPING_ACCESS_TOKEN  Please take these steps:
#     1) Go to http://www.pctools.com/guides/password/     (you can use another random password generator if you like)
#     2) Check ALL the boxes EXCEPT the punctuation box
#     3) Set the LENGTH to 31
#     4) Press the Generate Password button
#     5) Copy the generated password value to the clipboard
#     6) Now, in THIS file (ShippingZSettings.php): 
#        Go to the SHIPPING_ACCESS_TOKEN line below. Paste the random password from step 5 above in over the "CHANGE THIS" -- Note: keep the "quotes"
#        Example: define("SHIPPING_ACCESS_TOKEN","phe6uth3VEch3crutep2unepabupHa2");
#	  7) Save the this token, you will need it later on during the set up process.
#     8) Upload the full kit of files to the root directory of your ecommerce system
#        (This is the root directory of the web store containing 'index.php'). 
#        (Yes, it is OK to omit the files for other ecommerce systems. E.g. a Magento user 
#         can remove the Zencart and Oscommerce files.)
#     8) Continue through the ShipRush wizard. 
#     9) When the ShipRush wizard prompts for the Access Token, enter the token you used in step 4 above.
#     10) Scroll down through the sections below. You will a see a section marked 'Only for <your cart> users'. 
#		You will need to follow the steps in that section.
#
#   NOTE: Some systems require the file permissions of all the ShippingZ files to be 0444. This is read only for everyone.
#		  
# 
############################################## All Users Settings #######################################

define("SHIPPING_ACCESS_TOKEN","duzEnumEtreFr3qubruhucAmUWutHuw");  // See steps above to set this -- REQUIRED !


############################################## BEGIN Magento Section ##################################################
#
define("Magento_StoreShippingInComments",0); // Set to 0 for Magento v1.3 & 1.4. Set to 1 for v1.2. 
#                                            // If set to 1, comments will be posted in the general comments area on the order. 
# The setting below, "Magento_SendsShippingEmail"
#
# If set to 1, causes Magento to send the "Shipping Notification" email template configured in
# Magento's Admin > System > Transactional Emails section. Specifically, the template
# named "New Shipment" is emailed if this is set to 1.
define("Magento_SendsShippingEmail",0);    
#
define("Magento_SendsShippingEmail_AddComments",0);  // defaults to 0, False, which suppresses our comments in "new shipment" notification email
#
define("StandardPerformanceTest",0);//defaults 0   
#===================================================
#Setting below, Magento_Enterprise_Edition, set to 1 for Magento Enterprise Edition
define("Magento_Enterprise_Edition",0);//defaults 0
#
#
# Setting below, Magento_SendsBuyerEmail,
#
# If set to 1, causes Magento to send the "Order Update to buyer" email template configured in
# Magento's Admin > System > Transactional Emails section. Specifically, the template
# named "Order Update" is emailed to buyer if this is set to 1. Further, comments about the shipment
# are merged into this email
define("Magento_SendsBuyerEmail",0);   
#
# This next section controls which order statuses are read from Magento.
# Setting to 0 (zero) turns off retrieval of that status.
# Setting to 1 (one) turns on retrieval of that status.
# By default, all statuses are retrieved EXCEPT for "Pending"
define("MAGENTO_RETRIEVE_ORDER_STATUS_1_PENDING",0); // default 0
define("MAGENTO_RETRIEVE_ORDER_STATUS_2_PROCESSING",1); // default 1
define("MAGENTO_RETRIEVE_ORDER_STATUS_3_COMPLETE",1); // default 1
define("MAGENTO_RETRIEVE_ORDER_STATUS_4_CLOSED",0); // default 0
define("MAGENTO_RETRIEVE_ORDER_STATUS_4_CANCELLED",1); // default 1
#
# Next question: How do you work with the Magento Order Status?
# In other words, when an order is shipped, should the order
# always be set to COMPLETE in Magento?
#
# If you only ship orders that are in a PROCESSING state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: COMPLETE.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSING, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Magento.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (Pending) and STATUS 2 (Processing) will be
# set to STATUS 3 (Complete)
define("MAGENTO_SHIPPED_STATUS_COMPLETE_ALL_SHIPPED_ORDERS",1);// Default is 1
#
# Gift message settings. Controls whether the Magento Gift Message is retrieved.
# A setting of 1 means Yes.
# Normally, only one of these two options would be set to 1.
define("Magento_RetrieveOrderGiftMessage",1);     // ( default is 1 )
define("Magento_RetrieveProductGiftMessage",0);   // ( default is 0 )
#
# Store Settings: 
# - If you have only one store in your Magento system, please ignore the following.
# - If you have multiple stores, and want to retrieve orders from all stores, please ignore the following!
# - If you want to retrieve orders from only one store in a multi-store environment, set the following to
#   the "Store Code" to service.
# - If you want to retrieve orders from "multiple stores but not all stores.", enter comma separated store codes (eg. storecode1, storecode2,strorecode3)
# in below setting parameter i.e. Magento_Store_Code_To_Service. Hence it would read-
# define("Magento_Store_Code_To_Service","storecode1,storecode2,strorecode3"); 
#
#   TO FIND THE STORE CODE: 
#   - In the Magento Admin Panel, navigate to System | Manage Stores
#   - You get a list of stores.
#   - Click on the store. You now see the "Edit Store View" screen.
#   - The code to use for this next setting is the "Code" value.
#     For example, if the Code is "shoestore1" then the line below would read:
#       define("Magento_Store_Code_To_Service","shoestore1");
define("Magento_Store_Code_To_Service","-ALL-");  // default -ALL-, which retrieves from all stores on the Magento system
define("MAGENTO_READ_INVOICES",0);  // default 0, If set to 1 invoice numbers are retrieved
#
#
# Multi Currency Magento Store -> View Order As Base Currency Mode- Default 0 //Default will use Different Currency of webstore
define("MAGENTO_MULTI_CURRENCY_VIEW_AS_BASE_CURRENCY",0); // default 0, If set to 1 then Base currency value will be imported for the order
############################################## END Magento Section ##################################################


################################################ Only for Zencart Users ############################################ 
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("ZENCART_RETRIEVE_ORDER_STATUS_1_PENDING",1);
define("ZENCART_RETRIEVE_ORDER_STATUS_2_PROCESSING",1);
define("ZENCART_RETRIEVE_ORDER_STATUS_3_DELIVERED",1);
define("ZENCART_RETRIEVE_ORDER_STATUS_4_UPDATE",0);
# Next question: How do you work with the Zencart Order Status?
# In other words, when an order is shipped, should the order
# always be set to DELIVERED in Zencart?
#
# If you only ship orders that are in a PROCESSING state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: DELIVERED.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSING, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Zencart.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (Pending) and STATUS 2 (Processing) will be
# set to STATUS 3 (Delivered)
define("ZENCART_SHIPPED_STATUS_SET_TO_STATUS_3_DELIVERED",1); //Default: 1
#
#
################ Rarely used Zen Cart settings below ###############
# 
# Detect if orders are cancelled in Zen Cart? (Refer to Zen Cart docs about this area)
define("ZENCART_RETRIEVE_ORDER_STATUS_5_CANCELLED", 0);  //Default: 0. If you want to detect cancels in Zen Cart, set to 1
#  ALSO the "ZENCART_CANCELLED_ORDER_STATUS_ID" (below) must be set to the appro
#														   value on your system!
# 
# Enter cancelled order Status ID below to retrieve cancelled orders
define("ZENCART_CANCELLED_ORDER_STATUS_ID", 0);
#
define("ZENCART_ADMIN_DIRECTORY","admin");  // = /admin/
#
############################################## END Zencart Section ##################################################

################################################ Only for Oscommerce Users ############################################
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("OSCOMMERCE_RETRIEVE_ORDER_STATUS_1_PENDING",1);
define("OSCOMMERCE_RETRIEVE_ORDER_STATUS_2_PROCESSING",1);
define("OSCOMMERCE_RETRIEVE_ORDER_STATUS_3_DELIVERED",1);

################ Rarely used OsCommerce settings below ###############
# 
# Handle cancelled orders?
define("OSCOMMERCE_RETRIEVE_ORDER_STATUS_4_CANCELLED", 0);  //Default: 0. If you want to detect cancels in Zen Cart, set to 1
# 
# Enter cancelled order Status ID below to retrieve cancelled orders
define("OSCOMMERCE_CANCELLED_ORDER_STATUS_ID", 0);
#
#
########################################  PAYPAL RELATED SETTING ######################################## 
#If you have special order status [Paypal(transactions)] and want to retrieve those orders, then please, use following setting:
#
define("OSCOMMERCE_RETRIEVE_PAYPAL_ORDER_STATUS_ID", 4); //Defaults 0, enter paypal related order status id here
#
#
# Next question: How do you work with the Oscommerce Order Status?
# In other words, when an order is shipped, should the order
# always be set to DELIVERED in Oscommerce?
#
# If you only ship orders that are in a PROCESSING state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: DELIVERED.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSING, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Oscommerce.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (Pending) and STATUS 2 (Processing) will be
# set to STATUS 3 (Delivered)
define("OSCOMMERCE_SHIPPED_STATUS_SET_TO_STATUS_3_DELIVERED",1);//Defaults 1
#
define("OSCOMMERCE_ADMIN_DIRECTORY","admin");  // = /admin/
#
############################################## END Oscommerce Section ##################################################

################################################ Only for CRELOADED Users ############################################
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("CRELOADED_RETRIEVE_ORDER_STATUS_1_PENDING",1);
define("CRELOADED_RETRIEVE_ORDER_STATUS_2_PROCESSING",1);
define("CRELOADED_RETRIEVE_ORDER_STATUS_3_DELIVERED",1);
#
# Next question: How do you work with the Creloaded Order Status?
# In other words, when an order is shipped, should the order
# always be set to DELIVERED in Creloaded?
#
# If you only ship orders that are in a PROCESSING state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: DELIVERED.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSING, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Creloaded.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (Pending) and STATUS 2 (Processing) will be
# set to STATUS 3 (Delivered)
#
define("CRELOADED_SHIPPED_STATUS_SET_TO_STATUS_3_DELIVERED",1);//Defaults 1
#
define("CRELOADED_ADMIN_DIRECTORY","admin");  // = /admin/
define("CRELOADED_VERSION_7","0"); //Defaults: 0, set to 1 for CRELOADED v7
#
############################################## END CRELOADED Section ##################################################

################################################ Only for Xcart Users ################################################
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("XCART_RETRIEVE_ORDER_STATUS_1_QUEUED",0);
define("XCART_RETRIEVE_ORDER_STATUS_2_PROCESSED",1);
define("XCART_RETRIEVE_ORDER_STATUS_3_COMPLETE",1);
# Next question: How do you work with the Xcart Order Status?
# In other words, when an order is shipped, should the order
# always be set to COMPLETE in Xcart?
#
# If you only ship orders that are in a PROCESSING state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: COMPLETE.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSING, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Xcart.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (Pending) and STATUS 2 (Processing) will be
# set to STATUS 3 (Complete)
#
define("XCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE",1); //Default: 1
define("XCART_VERSION_5","0"); // Default: 0.  0 means XCart v4.x, 1 means XCart v5.x
#
############################################## END Xcart Section ##################################################

################################################ Only for Ubercart Users ############################################
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("UBERCART_RETRIEVE_ORDER_STATUS_1_PENDING",1);
define("UBERCART_RETRIEVE_ORDER_STATUS_2_PAYMENT_RECEIVED",1);
define("UBERCART_RETRIEVE_ORDER_STATUS_3_PROCESSING",1);
define("UBERCART_RETRIEVE_ORDER_STATUS_4_DELIVERED",1);
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# STATUS 1 (Pending), STATUS 2 (Payment Receive) and STATUS 3 (processing) will be
# set to STATUS 4 (Complete)
define("UBERCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE",1); //Default: 1
#
# Set to 1 to create shipments and packages in the Ubercart system when posting back tracking info
define('UBERCART_CREATE_SHIPMENT_AND_PACKAGE',0); //Default: 0
############################################## END Ubercart Section ##################################################

################################################ Only for Drupal7 with commerce Users ############################################
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("DRUPAL_RETRIEVE_ORDER_STATUS_1_PENDING",1);
define("DRUPAL_RETRIEVE_ORDER_STATUS_2_PAYMENT_RECEIVED",1);
define("DRUPAL_RETRIEVE_ORDER_STATUS_3_PROCESSING",1);
define("DRUPAL_RETRIEVE_ORDER_STATUS_4_DELIVERED",1);
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# STATUS 1 (Pending), STATUS 2 (Payment Receive) and STATUS 3 (processing) will be
# set to STATUS 4 (Complete)
define("DRUPAL_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE",1); //Default: 1
#
############################################## END Drupal Section ##################################################

################################################ Only for Cscart with commerce Users ############################################
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("CSCART_RETRIEVE_ORDER_STATUS_1_OPEN",1);
define("CSCART_RETRIEVE_ORDER_STATUS_2_PROCESSED",1);
define("CSCART_RETRIEVE_ORDER_STATUS_3_COMPLETE",1);
define("CSCART_RETRIEVE_ORDER_STATUS_5_CANCELLED",0); // Default is 0, set 1 to retrieve cancelled orders
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# STATUS 1 (Open), STATUS 2 (Processed) will be
# set to and STATUS 3 (Complete)
define("CSCART_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETE",1);	// Default is 1
# Which notes should be updated with track no?
# By default, only the Staff facing notes are updated.
define("CSCART_UPDATE_STAFF_NOTES",1);  					// Default is 1
# To update customer facing notes, set below to 1
define("CSCART_UPDATE_CUSTOMER_NOTES",0);   				// Default is 0
# Set following parameter to 1 for cscart professional version
define("CSCART_PROFESSIONAL",0);// Default : 0   Set to 0 for the free, non-professional version of CS Cart.
# The following is only used if on CS Cart 3 or earlier 
define("CSCART_ULTIMATE",1); // Default : 1   Always set to 1 for CS-Cart release 4 and up. For v3 and down, set this value to 1 only if it is CS-Cart Ultimate version otherwise set to 0. 
#
############################################## END Cscart Section ##################################################

################################################ Only for Opencart Users ############################################ 
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("OPENCART_RETRIEVE_ORDER_STATUS_1_UNPAID",1);
define("OPENCART_RETRIEVE_ORDER_STATUS_2_PAID",1);
define("OPENCART_RETRIEVE_ORDER_STATUS_3_SHIPPED",1);
# Next question: How do you work with the Opencart Order Status?
# In other words, when an order is shipped, should the order
# always be set to SHIPPED in Opencart?
#
# If you only ship orders that are in a PROCESSING state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: SHIPPED.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSING, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Opencart.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (UNPAID) and STATUS 2 (PAID) will be
# set to STATUS 3 (Shipped)
define("OPENCART_SHIPPED_STATUS_SET_TO_STATUS_3_SHIPPED",1); //Default  1
#
# Opencart Order statuses. Order status ids under different categories (like 
# Paid, Unpaid & Shipped) can be adjusted according to the store.
# Comma separated order ids should be used.For example: 1,8,10
#
# Steps to see the numeric value of order statuses:
# 1)Login to Opencart Admin section
# 2)Go to Sales>>Orders
# 3)Check the HTML source code using View page source option in your browser
# 4)In the source code search for name="filter_order_status_id"
# 5)You will be able to view order status ids (inside select box option values) as shown in the attachment.
#
define("OPENCART_UNPAID_ORDER_STATUSES","1,8,10");
define("OPENCART_PAID_ORDER_STATUSES","5,15");
define("OPENCART_SHIPPED_ORDER_STATUSES","3");
#
############################################## END Opencart Section ##################################################

################################################ Only for PrestaShop Users ############################################ 
#
# NOTE: The PSWebServiceLibrary must be installed and functional in your Prestashop system.
#       (Some installations of Prestashop lack this file and it must be manually installed.)
#       Please consult with the Prestashop community to confirm that the Prestashop web service
#       is present and functioning on your system!
#
# The PRESTASHOP_API_KEY is how we authenticate to Prestashop
# The Web Service key is generated in the Prestashop Tools | Web Service screen, and
# needs to be pasted here (below) AND in the Web Store configuration screen.
# 
# To generate a Web Service Key:
#   Go to the Prestashop Tools | Web Service screen
#   Create a Web Service account
#   Generate a new Key
#   Make sure the permissions are set to allow access to order & customer details
#   Paste the key here (below, where the #### is) and into the Web Store
#
define("PRESTASHOP_API_KEY","####");  // See above for details    
#   
define("ShowPackItems",0);// default FALSE, set 1 for packed products
#
#
define("PRESTASHOP_RETRIEVE_ORDER_STATUS_2_AWAITING_PAYMENT",1);
define("PRESTASHOP_RETRIEVE_ORDER_STATUS_1_PAYMENT_ACCEPTED",1);
define("PRESTASHOP_RETRIEVE_ORDER_STATUS_2_PROCESSING",1);
define("PRESTASHOP_RETRIEVE_ORDER_STATUS_3_SHIPPED",0);
# Next question: How do you work with the PrestaShop Order Status?
# In other words, when an order is shipped, should the order
# always be set to SHIPPED in PrestaShop?
#
# If you only ship orders that are in a "Preparation on Progress" state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: SHIPPED.
#
# However, if you have "Payment Accepted" orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSING i.e. "Preparation on Progress" state, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into prestashop.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (Payment Accepted) and STATUS 2 (Preparation on Progress) will be
# set to STATUS 3 (Shipped)
define("PRESTASHOP_SHIPPED_STATUS_SET_TO_STATUS_3_SHIPPED",1); //Defaults 1
#
// PrestaShop V.1.5, MultiStore Setting
// Default: -ALL-, which retrieves orders from all shops on the PrestaShop installation
// To retrieve only from specific shop(s): Enter the relevant Shop id ("id_shop") 
// To retrieve from multiple shops (but not all), enter a comma separated list of shop ids(e.g.: 1,3).
define("PRESTASHOP_Shop_Id_To_Service","-ALL-");  // default -ALL-, which retrieves from all shop orders on the PrestaShop store
// define("PRESTASHOP_Shop_Id_To_Service","1,3,5");  // This example retrieves from shop id's 1, 3 and 5.
#
############################################## END PrestaShop Section ##################################################

################################################ Only for WooCommerce Users ############################################
#
#  To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
#  Woo Setup Note: For WooCommerce 2.1.x you need to set the Consumer Key and Consumer Secret below.
#  To get these values from your Woocommerce system, please follow these steps:
#  -- Log in to your WP-Admin and enable the REST API from Woocommerce Settings. 
#  -- Then go to your user profile to generate your API keys 
#  -- Copy those values to the settings below, 

define("WOO_CONSUMER_KEY",'Enter consumer key here');
define("WOO_CONSUMER_SECRET",'Enter consumer secret here');

define("WOO_RETRIEVE_ORDER_STATUS_0_ON_HOLD",0);	// Default is 0
define("WOO_RETRIEVE_ORDER_STATUS_1_PENDING",1); 	// Default is 1
define("WOO_RETRIEVE_ORDER_STATUS_2_PROCESSING",1);	// Default is 1
define("WOO_RETRIEVE_ORDER_STATUS_3_COMPLETE",0);	// Default is 0
define("WOO_RETRIEVE_ORDER_STATUS_4_CANCELLED",0);	// Default is 0
#
# Short Explanation of below status setting:
# If above status is set to 1, then, when shipped, orders of
# STATUS 1 (Pending), STATUS 2 (Processed) will be
# set to and STATUS 3 (Complete)
define("WOO_SHIPPED_STATUS_SET_TO_STATUS_3_COMPLETE",1);	// Default is 1
#
define("SHIPMENT_TRACKING_MODULE","0"); // Default: 0.  Set to 1 if the Woothemes shipment tracking module is in use (http://www.woothemes.com/products/shipment-tracking/)
#
define("WOO_TRACKING_NOTES_UPDATE_ONLY","0"); // Default: 0.  Set to 1 if you wish to update tracking info or notes only and keep the Order Status as it was
#
############################################## END WooCommerce Section ##################################################
################################################ Only for Cubecart Users ############################################
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("CUBECART_RETRIEVE_ORDER_STATUS_1_PENDING",0);
define("CUBECART_RETRIEVE_ORDER_STATUS_2_PROCESSING",1);
define("CUBECART_RETRIEVE_ORDER_STATUS_3_COMPLETED",1);
define("CUBECART_RETRIEVE_ORDER_STATUS_3_DECLINED",0);
define("CUBECART_RETRIEVE_ORDER_STATUS_3_FAILED_FRAUD_REVIEW",0);
################ Rarely used Cubecart settings below ###############
# 
# Handle cancelled orders?
define("CUBECART_RETRIEVE_ORDER_STATUS_4_CANCELLED", 0);  //Default value is 0. If you want to detect cancels in Cubecart, set to 1
# 
# Enter cancelled order Status ID below to retrieve cancelled orders
define("CUBECART_CANCELLED_ORDER_STATUS_ID", 6);

# Next question: How do you work with the Cubecart Order Status?
# In other words, when an order is shipped, should the order
# always be set to DELIVERED in Cubecart?
#
# If you only ship orders that are in a PROCESSING state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: COMPLETED.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSING, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Cubecart.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (Pending) and STATUS 2 (Processing) will be
# set to STATUS 3 (Completed)
define("CUBECART_SHIPPED_STATUS_SET_TO_STATUS_3_DELIVERED",1);//Defaults 1
################################################ Only for Virtuemart Users ############################################
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("VIRTUEMART_RETRIEVE_ORDER_STATUS_1_PENDING",0);
define("VIRTUEMART_RETRIEVE_ORDER_STATUS_2_CONFIRMED_BY_SHOPPER",1);
define("VIRTUEMART_RETRIEVE_ORDER_STATUS_3_CONFIRMED",1);
define("VIRTUEMART_RETRIEVE_ORDER_STATUS_4_SHIPPED",0);
define("VIRTUEMART_RETRIEVE_ORDER_STATUS_6_REFUNDED",0);
################ Rarely used Virtuemart settings below ###############
# 
# Handle cancelled orders?
define("VIRTUEMART_RETRIEVE_ORDER_STATUS_5_CANCELLED",0);  //Default: 0. If you want to detect cancels in Virtuemart, set to 1
# 
# Next question: How do you work with the Virtuemart Order Status?
# In other words, when an order is shipped, should the order
# always be set to SHIPPED in Virtuemart?
#
# If you only ship orders that are in a CONFIRMED state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: SHIPPED.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# CONFIRMED, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Virtuemart.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# BOTH STATUS 1 (PENDING) and STATUS 2 (CONFIRMED) will be
# set to STATUS 3 (SHIPPED)
define("VIRTUEMART_SHIPPED_STATUS_SET_TO_STATUS_4_SHIPPED",1);//Defaults 1
################################################ Only for Jmarket Users ############################################ 
#
# To retrieve statuses in addition to those here, see the section below "Adding New Order Statuses"
#
define("JMARKET_RETRIEVE_ORDER_STATUS_1_PENDING",1);
define("JMARKET_RETRIEVE_ORDER_STATUS_2_IN_PROGRESS",1);
define("JMARKET_RETRIEVE_ORDER_STATUS_3_PROCESSED",1);
define("JMARKET_RETRIEVE_ORDER_STATUS_4_COMPLETED",0);
# Next question: How do you work with the Jmarket Order Status?
# In other words, when an order is shipped, should the order
# always be set to COMPLETED in Jmarket?
#
# If you only ship orders that are in a PROCESSED state, you can leave 
# the setting below alone. When shipped, they will be moved automatically to the next
# status: COMPLETED.
#
# However, if you have PENDING orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# IN-PROGRESS OR  if you have IN-PROGRESS orders being retrieved (see setting above)
# AND when shipped, you want those orders to move to a status of
# PROCESSED, then the setting below should be set to 0 (zero)
#
# In all cases, when shipped, the tracking # is posted into Jmarket.
#
# Short Explanation:
# If this is set to 1, then, when shipped, orders of
# STATUS 1 (Pending), STATUS 2 (In-Progress) and  STATUS 3 (Processed) will be
# set to STATUS 4 (Completed)
define("JMARKET_SHIPPED_STATUS_SET_TO_STATUS_4_COMPLETED",1); //Default: 1
#
#
################ Rarely used Jamrket settings below ###############
# 
# Detect if orders are cancelled in Jmarket? 
define("JMARKET_RETRIEVE_ORDER_STATUS_5_CANCELLED", 0);  //Default: 0. If you want to detect cancels in Jmarket, set to 1

############################################## System Settings for Tech Support Only ##############################

define("HTTP_GET_ENABLED",1);//allow get method
define("GetUnshippedOrdersOnly",0);//set 1 to get unshipped orders only


############################################## Adding New Order Statuses #####################################

# Say you want the system to retrieve an order status in addition to what is already coded here.
# How?

# There are two areas to modify. This settings file, and the php file for your platform.
# Here is an example for OsCommerce (can be used for most other php based systems):

# Step 1: Add to this settings file (without the leading # comment symbol):
# define("OSCOMMERCE_RETRIEVE_ORDER_STATUS_4_PAID",1);

# Step 2: Modify ShippingZOscommerce.php

# Add to this section:
# //Prepare order status string based on settings
# if(OSCOMMERCE_RETRIEVE_ORDER_STATUS_4_PAID==1)   // if set to 1 in Settings
# {
#  if($order_status_filter=="")
#  {
#  $order_status_filter.="orders_status=ZZZ";
#  }
#  else
#  {
#  // The ZZZ is the actual value in the database as the order_status for Paid
#  // For the status you want to retrieve, look in the database to find the real value
#  // and use it in this code
#  $order_status_filter.=" OR orders_status=ZZZ";  
#
#
# CS-Cart: Adding Statuses:
#
# For CS-Cart, additional modification to the ShippingZCscart.php file is needed
# so that the order is marked as complete on the update.
#
# In this example, G is your new order status value. Out of the box, statuses of O, P, and C
# are handled. We will extend the system to handle a status of G for update.
#
# 1: Find this line:
#    $sql = "SELECT COUNT(*) as total_order FROM ?:orders WHERE status in('O','P','C') ?p"; 
#
# For the new status, add it to the list for the "in" clause:
#    $sql = "SELECT COUNT(*) as total_order FROM ?:orders WHERE status in('O','P','C','G') ?p"; 
#
# 2: Further down in the php file, locate this section:
#
#                if($current_order_status=='O'  )
#                    $change_order_status='P';
#                else if($current_order_status=='P')
#                    $change_order_status='C'; 
#
# For the new status, add a new "else if" block:
#
#                if($current_order_status=='O'  )
#                    $change_order_status='P';
#                else if($current_order_status=='P')
#                    $change_order_status='C';                  
#                else if($current_order_status=='G')  
#                    $change_order_status='C'; 
#
# Note: Additional control over the status value can be achieved, but involves further
# customization. Please engage a PHP developer to assist.

#********************************************** Shipment Tracking URLs *****************************************************************
#
# Below are for the values saved into certain ecommerce systems. Rarely need to be changed. 
#
define("USPS_URL","http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum=[TRACKING_NUMBER]");
define("UPS_URL","http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=[TRACKING_NUMBER]");
define("FEDEX_URL","http://www.fedex.com/Tracking?action=track&tracknumbers=[TRACKING_NUMBER]");
define("DHL_URL","http://www.dhl.com/content/g0/en/express/tracking.shtml?brand=DHL&AWB=[TRACKING_NUMBER]");


############################################## Legal Notices ######################################################

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


?>