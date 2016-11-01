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

############################################# Please Read These Instructions #######################################
#
#   Your Attention Please !
#
#   Please follow these steps carefully. Setting up ShipRush requires
#   that you have full FTP access to your ecommerce system, and that you
#   can upload files to it and perform normal system administration functions.
#
#   If any of this is hard or too technical, please retain a consultant
#   or ecommerce developer. 
#
#
#   Known Supported Ecommerce Versions:
#   NOTE: This is a list of tested versions. If your version is close to one of these, you
#         can safely use ShipRush with your system. For example, if listed here is 6.4.1, and
#         you use 6.4.3, all should be well. But if you run 7.5.3, and v7.x is not listed at all,
#         it may make sense to inquire about v7.5 support.
#
#     Magento: Community Edition: v1.9.1.0, v1.8.1.0, v1.7.0, v1.6.0, v1.5.1, 
#                                 v1.4.1.1, v1.4.0.1, 1.3.2.4, v1.3.2.3, v1.2.0
#
#              Enterprise Edition: v1.9
#
#              Note: Magento has at times released versions which have broken
#              internals. These builds are broken within the Magento Core, and
#              integration of many kinds is not possible.
#
#              Known broken versions of Magento: v1.4.1.0, v1.8 (Enterprise)
#
#     X-Cart: 4.6.2, 4.4.2, 4.4.0, 4.2.0, 4.1.11
#
#     OsCommerce: v2.2rc2a
#
#     Zen Cart: v1.5.1, v1.5, 1.3.9.d – h, & v1.3.8a (on PHP v4.x & v5.x)
#
#     CRE Loaded: v6.4.1a, v6.5x (on PHP v5.x)
#
#     Drupal Commerce: v7.14
#
#     OpenCart versions supported: 2.0, 1.5.x, 1.4.7, 1.4.1, 1.3.1 (on PHP v5.x)
#
#              Note: v1.2.x is known to be problematic. Versions 1.3.x and higher are expected to function properly
#
#     Ubercart
#
#              Ubercart v3.5 for Drupal 7 (tested with Drupal 7.14 and Drupal 7.22)
#              Ubercart v2.1x for Drupal 6 (v2.10 tested with Drupal 6.19)
#              Ubercart v1.9 for Drupal 5 (tested with Drupal 5.22)
#
#     CS-Cart 4.2.1, 4.0.1 Ultimate, 3.0, 2.2.4, 2.2.3 (all on PHP v5.x), including Community, Professional, Multi-Vendor editions
#
#     PrestaShop versions supported: v1.6.0.6, v1.5.6.0, v1.5.3.1, v1.5.1, v1.4.6.2
#
#     WooCommerce: Full testing on 2.0.2 and 2.1.X. All versions in between are supported.
#
#
#   For all the above systems, you may try other versions. Please let us know if you are successful.
#
#
#   INSTALLATION takes just a few minutes:
#
#   1) Unzip this package on your local computer
#
#   2) Edit ShippingZSettings.php.  See Instructions within ShippingZSettings.php
#
#
#
#
#
#                     FAQ
#   Q: I need to modify the PHP code to read my order statuses. Can ShipRush support help out?
#
#   A: No, sorry! If the stock PHP is not behaving properly against a stock cart, please
#      tell us (we want to fix it). However, the support team cannot help modify PHP code.
#      Please engage a PHP consultant who knows your cart to assist with 
#      modification.
#
#   Q: My cart is heavily customized. Can I use this ShipRush integration?
#
#   A: Yes, but coding (by you or your team) be be needed.
#      If in doubt, have your developer review this plugin. 
#      Reading data is passive, and should not be able to damage anything.
#      You may make changes to this module if necessary to accomodate your customizations.
#
#   Q: I have a completely custom PHP system. Can I use this?
#
#   A: Yes, coding is required (by you or your team).
#      You will need to modify the plugin layer to interact with your system.
#      Go ahead and do this, but note that there is no support from us for custom coding.
#
####################################################################################################################