<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Shipping
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 *
 * @category   Webshopapps
 * @package    Webshopapps_shippingoveride2
 * @copyright   Copyright (c) 2013 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2013, Zowta, LLC - US license
 * @license    http://www.webshopapps.com/license/license.txt - Commercial license
 */
class Webshopapps_Shippingoverride2_Model_Mysql4_Shippingoverride2 extends Mage_Core_Model_Mysql4_Abstract
{

	private $_request;
	private $_zipSearchString;
	private $_table;
	private $_customerGroupCode;
	private $_starIncludeAll;
	private $_useParent;
	private $_freeShipping;
	private $_filterPrice;
	private $_debug;
	private $_totalShipPrice;
	private $_disablePromotions;
	private $_highest = 999;
    private $_usingPriorities = false;
    private $_options;
    private $_runningCartPrice = 0;
    private $_productRateInstalled = false;

    protected function _construct()
    {
        $this->_init('shipping/shippingoverride2', 'pk');
    }

    public function initGlobals()
    {
        $this->_debug = Mage::helper('wsalogger')->isDebug('Webshopapps_Shippingoverride2');

        $this->_options = explode(',',Mage::getStoreConfig("shipping/shippingoverride2/ship_options"));
        $this->_starIncludeAll = Mage::getStoreConfig('shipping/shippingoverride2/star_include_all');
        $this->_disablePromotions = Mage::getStoreConfig("shipping/shippingoverride2/disable_promotions");
        $this->_useParent = Mage::getStoreConfig('shipping/shippingoverride2/use_parent');

        $this->_filterPrice = in_array('subtotalpw',$this->_options);
        $this->_freeShipping = $this->_request->getFreeShipping();
        $this->_totalShipPrice=0;
        $this->_productRateInstalled = Mage::helper('wsacommon/shipping')->isModuleEnabled('Webshopapps_Productrate');
        $this->_table = Mage::getSingleton('core/resource')->getTableName('shippingoverride2/shippingoverride2');
    }

    public function getNewRate(Mage_Shipping_Model_Rate_Request $request, &$exclusionList, &$error, &$weightIncArr)
    {
        $this->_request=$request;

        $this->initGlobals();

    	$zipRangeSet = Mage::getStoreConfig('shipping/shippingoverride2/zip_range');

        $read = $this->_getReadAdapter();

        if ($this->_debug) {
        	Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Found these advanced options in effect',$this->_options);
        }

    	$postcode = $request->getDestPostcode();

		if ($zipRangeSet) {
			#  Want to search for postcodes within a range
			$zipSearchString = $read->quoteInto(" AND dest_zip<=? ", $postcode).
								$read->quoteInto(" AND dest_zip_to>=? )", $postcode);
		} else {
			$zipSearchString = $read->quoteInto(" AND ? LIKE dest_zip )", $postcode);
		}

        $this->_zipSearchString=$zipSearchString;

    	if ($this->_debug) {
    		Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Street Address',$request->getDestStreet());
    	}

        // if POBOX search on CITY field
        $searchPOBox=false;
        if (preg_match('/(^|(?:post(al)? *(?:office *)?|p[. ]*o\.? *))box *#? *\w+/ui', $request->getDestStreet())) {
  			$searchPOBox=true;
    		if ($this->_debug) {
    			Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Can\'t Deliver to POBOX','');
    		}
		}

		$items = $request->getAllItems();
    	$this->_customerGroupCode = $this->getCustomerGroupCode();
		if ($this->_debug) {
			Mage::helper('wsalogger/log')->postInfo('shippingoverride2','Customer Group Code',$this->_customerGroupCode);
		}

		// get the special_shipping_group's for the items in the cart

		$structuredItems = $this->getStructuredItems($items);
		$deliveryOverrideList=array();
		$numGroups = $this->_starIncludeAll ? count($structuredItems)-1 : count($structuredItems);

		foreach ($structuredItems as $group=>$structuredItem) {
			if ($group=='none' && $this->_starIncludeAll) { continue; }
			if ($this->_debug) {
				Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Shipping Group',$group);
			}
			$rates=$this->runSelectStmt($read,$structuredItem,$group,$searchPOBox);
			if ($this->_debug) {
				Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Rates Found',$rates);
			}
			if (!empty($rates)) {
				// found something - what do we do with this?
				foreach ($rates as $rate) {
                    $surchargeLiftgate = false;
                    if ($rate['price']==-1) {
						switch ($rate['algorithm']) {
							case 'ORDER_SINGLE':
								if ($numGroups>1) {
									break;
								}
								// exclude delivery type from shipping
								if (!in_array($rate['delivery_type'],$exclusionList)) {
									$exclusionList[]=$rate['delivery_type'];
									if (array_key_exists('rules', $rate) && $rate['rules']!='') {
										$this->getCustomError($rate, $error);
									}
								}
								break;
							case 'ORDER_MERGED':
								if ($numGroups==1) {
									break;
								}
							default:
								// exclude delivery type from shipping
								if (!in_array($rate['delivery_type'],$exclusionList)) {
									$exclusionList[]=$rate['delivery_type'];
									if (array_key_exists('rules', $rate) && $rate['rules']!='') {
										$this->getCustomError($rate, $error);
									}
								}
								break;
						}
						continue;
					}

					switch ($rate['algorithm']) {
						case 'OVERRIDE':
							$deliveryOverrideList[$rate['delivery_type']] = array (
																			'ship_price' 		=> $rate['price'],
																			'ship_percent' 		=> $rate['percentage'],
																			'exclude_groups' 	=> array ($group),
																			'include_groups' 	=> array (),
																			'wipe_rate'			=> true,
																			'surcharge'			=> false,
																			'override'			=> true,
                                                                            'weight_increase'   => 0,
							);
							if ($rate['rules']!='') {
								$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
							}
							break;
                        case 'ORDER_LIFT':
                            $surchargeLiftgate = true;
                        case 'ORDER_SINGLE':
							if ($numGroups>1 && $rate['algorithm']=='ORDER_SINGLE') {
								break;
							}
						case 'ORDER':
							// flat rate override2, get flat totals
							if (array_key_exists($rate['delivery_type'],$deliveryOverrideList) ) {
								if ( !$deliveryOverrideList[$rate['delivery_type']]['override']) {
									$deliveryOverrideList[$rate['delivery_type']]['ship_price']+=$rate['price'];
									$deliveryOverrideList[$rate['delivery_type']]['ship_percent']+=$rate['percentage'];
									$deliveryOverrideList[$rate['delivery_type']]['exclude_groups'][]=$group;
                                    $deliveryOverrideList[$rate['delivery_type']]['surcharge']=false;
                                    $deliveryOverrideList[$rate['delivery_type']]['surcharge_liftgate']=$surchargeLiftgate;

									if ($rate['rules']!='') {
										$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
									}
								}
							} else {
								$deliveryOverrideList[$rate['delivery_type']] = array (
																				'ship_price' 		=> $rate['price'],
																				'ship_percent' 		=> $rate['percentage'],
																				'exclude_groups' 	=> array ($group),
																				'include_groups' 	=> array (),
																				'wipe_rate'			=> true,
																				'surcharge'			=> false,
																				'override'			=> false,
                                                                                'surcharge_liftgate'=> $surchargeLiftgate,
                                                                                'weight_increase'   => 0,
                                );
								if ($rate['rules']!='') {
									$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
								}
							}
							break;
						case 'ORDER_MERGED':
							if ($numGroups==1) {
								break;
							}
							// same as ORDER
							if (array_key_exists($rate['delivery_type'],$deliveryOverrideList) ) {
								if ( !$deliveryOverrideList[$rate['delivery_type']]['override']) {
									$deliveryOverrideList[$rate['delivery_type']]['ship_price']+=$rate['price'];
									$deliveryOverrideList[$rate['delivery_type']]['ship_percent']+=$rate['percentage'];
									$deliveryOverrideList[$rate['delivery_type']]['surcharge']=false;
									$deliveryOverrideList[$rate['delivery_type']]['exclude_groups'][]=$group;
									if ($rate['rules']!='') {
										$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
									}
								}
							} else {
								$deliveryOverrideList[$rate['delivery_type']] = array (
																				'ship_price' 		=> $rate['price'],
																				'ship_percent' 		=> $rate['percentage'],
																				'exclude_groups' 	=> array ($group),
																				'include_groups' 	=> array (),
																				'surcharge'			=> false,
																				'wipe_rate'			=> true,
																				'override'			=> false,
                                                                                'weight_increase'   => 0,
								);
								if ($rate['rules']!='') {
									$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
								}
							}
							break;
						case 'ITEM':
							// flat rate override2, get flat totals
							if (array_key_exists($rate['delivery_type'],$deliveryOverrideList)) {
								if ( !$deliveryOverrideList[$rate['delivery_type']]['override']) {
									$deliveryOverrideList[$rate['delivery_type']]['ship_price']+=$rate['price']*$structuredItem['qty'];
									$deliveryOverrideList[$rate['delivery_type']]['ship_percent']+=$rate['percentage'];
									$deliveryOverrideList[$rate['delivery_type']]['surcharge']=false;
									$deliveryOverrideList[$rate['delivery_type']]['exclude_groups'][]=$group;
									if ($rate['rules']!='') {
										$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
									}
								}
							}else {
								$deliveryOverrideList[$rate['delivery_type']] = array (

																				'ship_price' 		=> $rate['price']*$structuredItem['qty'],
																				'ship_percent' 		=> $rate['percentage'],
																				'exclude_groups' 	=> array ($group),
																				'include_groups' 	=> array (),
																				'wipe_rate'			=> true,
																				'surcharge'			=> false,
																				'override'			=> false,
                                                                                'weight_increase'   => 0,
								);
								if ($rate['rules']!='') {
									$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
								}
							}
							break;
						case 'ADD_ITEM':

							if (array_key_exists($rate['delivery_type'],$deliveryOverrideList)) {
								if ( !$deliveryOverrideList[$rate['delivery_type']]['override']) {
									$deliveryOverrideList[$rate['delivery_type']]['ship_price']+=$rate['price']*($structuredItem['qty']-$rate['item_from_value']);
									$deliveryOverrideList[$rate['delivery_type']]['ship_percent']+=$rate['percentage'];
									$deliveryOverrideList[$rate['delivery_type']]['exclude_groups'][]=$group;
									$deliveryOverrideList[$rate['delivery_type']]['surcharge']=false;
									if ($rate['rules']!='') {
										$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
									}
								}
							}else {
								$deliveryOverrideList[$rate['delivery_type']] = array (

																				'ship_price' 		=> $rate['price']* ($structuredItem['qty']-$rate['item_from_value']),
																				'ship_percent' 		=> $rate['percentage'],
																				'exclude_groups' 	=> array ($group),
																				'include_groups' 	=> array (),
																				'wipe_rate'			=> true,
																				'surcharge'			=> false,
																				'override'			=> false,
                                                                                'weight_increase'   => 0,
								);
								if ($rate['rules']!='') {
									$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
								}
							}
							break;
						case 'SURCHARGE_ORDER':
							// flat rate override2, get flat totals
							if (array_key_exists($rate['delivery_type'],$deliveryOverrideList)) {
								if ( !$deliveryOverrideList[$rate['delivery_type']]['override']) {
									$deliveryOverrideList[$rate['delivery_type']]['ship_price']+=$rate['price'];
									$deliveryOverrideList[$rate['delivery_type']]['ship_percent']+=$rate['percentage'];
									$deliveryOverrideList[$rate['delivery_type']]['include_groups'][]=$group;
									if ($rate['rules']!='') {
										$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
									}
								}
								// keep items
							} else {

								$deliveryOverrideList[$rate['delivery_type']] = array (
																				'ship_price' 		=> $rate['price'],
																				'ship_percent' 		=> $rate['percentage'],
																				'exclude_groups' 	=> array (),
																				'include_groups' 	=> array ($group),
																				'wipe_rate'			=> false,
																				'surcharge'			=> true,
																				'override'			=> false,
                                                                                'weight_increase'   => 0,
								);
								if ($rate['rules']!='') {
									$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
								}
							}
							break;
						case 'SURCHARGE_ITEM':
							// flat rate override2, get flat totals
							if (array_key_exists($rate['delivery_type'],$deliveryOverrideList) ) {
								if ( !$deliveryOverrideList[$rate['delivery_type']]['override']) {
									$deliveryOverrideList[$rate['delivery_type']]['ship_price']+=$rate['price']*$structuredItem['qty'];
									$deliveryOverrideList[$rate['delivery_type']]['ship_percent']+=$rate['percentage'];
									$deliveryOverrideList[$rate['delivery_type']]['include_groups'][]=$group;
									if ($rate['rules']!='') {
										$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
									}
								}
								// keep items
							} else {
								$deliveryOverrideList[$rate['delivery_type']] = array (
																				'ship_price' 			=> $rate['price']*$structuredItem['qty'],
																				'ship_percent' 		=> $rate['percentage'],
																				'exclude_groups' 	=> array (),
																				'surcharge'			=> true,
																				'include_groups' 	=> array ($group),
																				'wipe_rate'			=> false,
																				'override'			=> false,
                                                                                'weight_increase'   => 0,
								);
								if ($rate['rules']!='') {
									$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
								}
							}
							break;

					 	case 'PERCENTAGE_CART':
							// flat rate override2, get flat totals
							if (array_key_exists($rate['delivery_type'],$deliveryOverrideList)) {
								if ( !$deliveryOverrideList[$rate['delivery_type']]['override']) {
								//	$deliveryOverrideList[$rate['delivery_type']]['product_ship_price'] += $structuredItem['shipping_price'];
									$deliveryOverrideList[$rate['delivery_type']]['ship_price']+=$rate['price'] + ($rate['percentage']/100)*$structuredItem['price'];
									$deliveryOverrideList[$rate['delivery_type']]['surcharge']=false;
									$deliveryOverrideList[$rate['delivery_type']]['exclude_groups'][]=$group;
									if ($rate['rules']!='') {
										$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
									}
								}
							} else {
								$deliveryOverrideList[$rate['delivery_type']] = array (
																				'ship_price' 		=> $rate['price']+($rate['percentage']/100)*$structuredItem['price'],
																				'exclude_groups' 	=> array ($group),
																				'surcharge'			=> false,
																				'ship_percent'		=> 0,
																				'include_groups' 	=> array (),
																				'wipe_rate'			=> true,
																				'override'			=> false,
                                                                                'weight_increase'   => 0,
								);
								if ($rate['rules']!='') {
									$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
								}
							}
							break;
						case 'SURCHARGE_PERCENTAGE_CART':
							// flat rate override2, get flat totals
							if (array_key_exists($rate['delivery_type'],$deliveryOverrideList)) {
								if ( !$deliveryOverrideList[$rate['delivery_type']]['override']) {
									$deliveryOverrideList[$rate['delivery_type']]['ship_price']+=$rate['price'] + ($rate['percentage']/100)*$structuredItem['price'];
									$deliveryOverrideList[$rate['delivery_type']]['include_groups'][]=$group;
									if ($rate['rules']!='') {
										$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
									}
								}
								// keep items
							} else {
								$deliveryOverrideList[$rate['delivery_type']] = array (
																				'ship_price' 		=> $rate['price']+($rate['percentage']/100)*$structuredItem['price'],
																				'ship_percent'		=> 0,
																				'exclude_groups' 	=> array ($group),
																				'include_groups' 	=> array (),
																				'wipe_rate'			=> false,
																				'surcharge'			=> true,
																				'override'			=> false,
                                                                                'weight_increase'   => 0,
								);
								if ($rate['rules']!='') {
									$deliveryOverrideList[$rate['delivery_type']]['rules'][]=$rate['rules'];
								}
							}
							break;
                        case 'WEIGHTINC':
                            if (!array_key_exists($group,$weightIncArr)) {
                                $weightIncArr[$group] = $rate['price'];
                            } else {
                                $weightIncArr[$group] += $rate['price'];  // double using here, this is actually weight value
                            }
                            break;
					}
				}
			}
		}

        if (count($weightIncArr)>0) {
            $weightIncArr['all_items'] = $this->_getTotalWeightIncrease($weightIncArr);
        }


        if ($this->_debug) {
			Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Override List',$deliveryOverrideList);
			Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Exclude List',$exclusionList);
            Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Custom Message',$error);
            Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Weight Increase',$weightIncArr);

        }

		if (count($deliveryOverrideList)<1) {
			// found nothing
			return array();
		}
		foreach ($deliveryOverrideList as $deliveryType=>$override2Details) {
			$excludeGroups = $override2Details['exclude_groups'];
			$deliveryOverrideList[$deliveryType]['product_ship_price']=0;
			$flatExclusionGroups=array();
			if (array_key_exists('rules', $override2Details)) {
				$this->processRules($override2Details['rules'], $flatExclusionGroups);

			}
			if (in_array('include_all',$excludeGroups)) {
				// the product_ship_price is subject to problems - will most likely be here
				if ( !$deliveryOverrideList[$deliveryType]['override']) {
					foreach ($structuredItems['include_all']['shipping_price_array'] as $shippingGroup=>$price) {
						if (!in_array($shippingGroup,$flatExclusionGroups)) {
							$deliveryOverrideList[$deliveryType]['product_ship_price'] += $price;
						}
					}
				}
				continue;


			}
			foreach ($structuredItems as $group=>$structuredItem) {

                if (array_key_exists($group,$weightIncArr)) {
                    $deliveryOverrideList[$deliveryType]['weight_increase'] += $weightIncArr[$group];
                }

				if ($group=='include_all') {
					if ( !in_array($group,$excludeGroups) &&
							!$deliveryOverrideList[$deliveryType]['override']) {
						foreach ($structuredItem['shipping_price_array'] as $shippingGroup=>$price) {
							if (!in_array($shippingGroup,$flatExclusionGroups)) {
								$deliveryOverrideList[$deliveryType]['product_ship_price'] += $price;
							}
						}
					}
					continue;
				}

				if (!$deliveryOverrideList[$deliveryType]['override']&& !in_array($group,$flatExclusionGroups)) {
					if (!$this->_starIncludeAll) {
						$deliveryOverrideList[$deliveryType]['product_ship_price']+= $structuredItem['shipping_price'];
					} else if ($structuredItems['include_all']['shipping_price_array'][$group]>0 &&
						!in_array($group,$excludeGroups) &&
						 (count($exclusionList)==0 || !in_array($group,$exclusionList))) {
						$deliveryOverrideList[$deliveryType]['product_ship_price']+= $structuredItem['shipping_price'];
					}
				}


				if ((count($exclusionList)>0 && in_array($group,$exclusionList)) || in_array($group,$excludeGroups)) {
					continue;
				}

				// need to get only the items that apply to this group
				// this could be everything
				if (array_key_exists('cart_details', $deliveryOverrideList[$deliveryType])) {
					$deliveryOverrideList[$deliveryType]['cart_details']['qty']+=$structuredItem['qty'];
					$deliveryOverrideList[$deliveryType]['cart_details']['weight']+=$structuredItem['weight'];
					$deliveryOverrideList[$deliveryType]['cart_details']['price']+=$structuredItem['price'];
					//$deliveryOverrideList[$deliveryType]['cart_details']['item_group']+= $structuredItem['item_group'];
					$deliveryOverrideList[$deliveryType]['cart_details']['item_group']=
						array_merge($structuredItem['item_group'],$deliveryOverrideList[$deliveryType]['cart_details']['item_group']);
				} else {
					$deliveryOverrideList[$deliveryType]['cart_details'] = array (
							'item_group' 		=>	$structuredItem['item_group'],
							'qty' 				=>	$structuredItem['qty'],
							'weight' 			=>	$structuredItem['weight'],
							'price' 			=>	$structuredItem['price'],
					);
				}
			}
		}


		// now check for those where it is total cart price

		foreach ($deliveryOverrideList as $deliveryType=>$override2Details) {
			if (array_key_exists('cart_details', $deliveryOverrideList[$deliveryType])) {
				if ($deliveryOverrideList[$deliveryType]['cart_details']['qty']==$request->getPackageQty()) {
					$deliveryOverrideList[$deliveryType]['cart_details']="";
					$deliveryOverrideList[$deliveryType]['whole_cart'] = true;
				} else {
					$deliveryOverrideList[$deliveryType]['whole_cart'] = false;
				}
			} else {
				$deliveryOverrideList[$deliveryType]['whole_cart'] = true;
			}
			if ($deliveryOverrideList[$deliveryType]['whole_cart'] && $deliveryOverrideList[$deliveryType]['ship_price']==0
				&& $deliveryOverrideList[$deliveryType]['ship_percent']==0 && !$deliveryOverrideList[$deliveryType]['surcharge'] ) {
				// Cant wipe this as may be a surcharge on order
				$deliveryOverrideList[$deliveryType]['wipe_rate'] = 1;
			}

			if ($this->_debug) {
                $tempLog['Delivery Type'] = $deliveryType;
                $tempLog['Exclude Groups'] = $override2Details['exclude_groups'];
                $tempLog['Product Ship Price'] = $override2Details['product_ship_price'];
                $tempLog['Ship Price'] = $override2Details['ship_price'];
                $tempLog['Include Groups'] = $override2Details['include_groups'];
                $tempLog['Weight Increase'] = $override2Details['weight_increase'];
                $tempLog['Whole Cart'] = $deliveryOverrideList[$deliveryType]['whole_cart'];
				Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Override List to use',$tempLog);
			}

		}

		//$totalShipPrice=$this->_totalShipPrice;
		//if ($this->_debug) {
		//	Mage::log($totalShipPrice);
		//}

		return $deliveryOverrideList;
    }

    protected function _getTotalWeightIncrease($weightIncArr) {
        $totalCartIncrease=0;
        foreach ($weightIncArr as $increaseValue) {
            $totalCartIncrease +=$increaseValue;
        }
        return $totalCartIncrease;
    }



    protected function getCustomerGroupCode() {

    	if ($ruleData = Mage::registry('rule_data')) {
            $gId = $ruleData->getCustomerGroupId();
            return Mage::getModel('customer/group')->load($gId)->getCode();
    	} else {
    		return Mage::getModel('customer/group')->load(
			Mage::getSingleton('customer/session')->getCustomerGroupId())->getCode();
    	}

    }


    private function processRules($rules, &$exclusionGroups ) {


    	$exclusionGroups=array();

    	foreach ($rules as $rule) {
  			$algorithm_array=explode("&",$rule);  // Multi-formula extension
			foreach ($algorithm_array as $algorithm_single) {
				$algorithm=explode("=",$algorithm_single,2);
				if (!empty($algorithm) && count($algorithm)==2) {
					if (strtolower($algorithm[0])=="ex_flat") {
						$exclusionGroups[]=$algorithm[1];
					}
				}
			}
    	}
    	if ($this->_debug) {
    		Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Rules in Effect',$exclusionGroups);
    	}
    }

    private function getCustomError($rate, &$error){
    	$ruleField=explode("&",$rate['rules']);

    	foreach ($ruleField as $i=>$part) {
    		$rule=explode("=",$part,2);

    		if (strtolower($rule[0])=="p" ) {
    			if ($rule[1] < $this->_highest) {
    				$this->_highest = $rule[1];
    				$this->_usingPriorities = true;
    				$errorCode = explode("=", $ruleField[$i+1]);
    				$error = $errorCode[1];
    			}
    		} else if (!$this->_usingPriorities && count($rule)==2) {
    			$error = $rule[1];
    		}
    	}
    }


	private function runSelectStmt($read,$structuredItem,$group,$searchPOBox)
	{

		if ($searchPOBox) {
			$destCity='POBOX';
		} else {
			$destCity=$this->_request->getDestCity();
		}

		for ($j=0;$j<9;$j++)
		{
			//$select = $read->select()->from($table);
			$select = $read->select()->from(array('shippingoverride2'=>$this->_table),
							array(	'pk'=>'pk',
									'price'=>'price',
									'percentage'=>'percentage',
									'delivery_type'=>'delivery_type',
									'algorithm'=>'algorithm',
									'special_shipping_group'=>'special_shipping_group',
									'rules'=>'rules',
									'item_from_value'=>'item_from_value'));

			switch($j) {
				case 0:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? ", $this->_request->getDestRegionId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  ", $destCity).
							$this->_zipSearchString
						);
					break;
				case 1:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=?  AND dest_city=''", $this->_request->getDestRegionId()).
							$this->_zipSearchString
						);
					break;
				case 2:
					$select->where(
						$read->quoteInto(" (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? ", $this->_request->getDestRegionId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_zip='')", $destCity)
						);
					break;
				case 3:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_region_id='0'", $destCity).
							$this->_zipSearchString
					   );
					break;
				case 4:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND STRCMP(LOWER(dest_city),LOWER(?)) = 0  AND dest_region_id='0' AND dest_zip='') ", $destCity)
					   );
					break;
				case 5:
					$select->where(
						$read->quoteInto("  (dest_country_id=? AND dest_region_id='0' AND dest_city='' ", $this->_request->getDestCountryId()).
							$this->_zipSearchString
						);
					break;
				case 6:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? ", $this->_request->getDestCountryId()).
							$read->quoteInto(" AND dest_region_id=? AND dest_city='' AND dest_zip='') ", $this->_request->getDestRegionId())
					   );
					break;

				case 7:
					$select->where(
					   $read->quoteInto("  (dest_country_id=? AND dest_region_id='0' AND dest_city='' AND dest_zip='') ", $this->_request->getDestCountryId())
					);
					break;

				case 8:
					$select->where(
							"  (dest_country_id='0' AND dest_region_id='0' AND dest_zip='')"
				);
					break;
			}

			if ($group=='include_all' || $group=='none') {
				$select->where('special_shipping_group=?','');
			} else {
				$select->where('special_shipping_group=?', $group);

			}

			if ($this->_filterPrice) {
				$select->where('weight_from_value<?', $this->_request->getPackageWeight());
				$select->where('weight_to_value>=?', $this->_request->getPackageWeight());

				if(Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Dropship') && Mage::getStoreConfig('carriers/dropship/use_cart_price')){
			    	$select->where('price_from_value<?', $this->_request->getPackageValue());
			    	$select->where('price_to_value>=?',  $this->_request->getPackageValue());
			    }else{
					$select->where('price_from_value<?', $this->_runningCartPrice);
					$select->where('price_to_value>=?', $this->_runningCartPrice);
				}

			} else {
				$select->where('weight_from_value<?', $structuredItem['weight']);
				$select->where('weight_to_value>=?', $structuredItem['weight']);
				$select->where('item_weight_from_value<?', $structuredItem['item_weight']);
				$select->where('item_weight_to_value>=?', $structuredItem['item_weight']);
				$select->where('price_from_value<?', $structuredItem['price']);
				$select->where('price_to_value>=?', $structuredItem['price']);
			}

			$select->where('item_from_value<?', $structuredItem['qty']);
			$select->where('item_to_value>=?', $structuredItem['qty']);

			$groupArr[0]="STRCMP(LOWER(customer_group),LOWER('".$this->_customerGroupCode."')) =0";
			$groupArr[1]="customer_group=''";
			$select->where(join(' OR ', $groupArr));

			$select->where('website_id=?', $this->_request->getWebsiteId());

			$select->order('price ASC');
			/*
			pdo has an issue. we cannot use bind
			*/

			$row = $read->fetchAll($select);

			if (!empty($row)) {
				if ($this->_debug) {
					Mage::helper('wsalogger/log')->postDebug('shippingoverride','SQL Select',$select->getPart('where'));
				}
				return $row;
			}

		}
		if ($this->_debug) {
			Mage::helper('wsalogger/log')->postDebug('shippingoverride','SQL Select',$select->getPart('where'));
		}
	}


	private function getStructuredItems($items)
	{
		$useParent = Mage::getStoreConfig('shipping/shippingoverride2/use_parent');
		$useDiscount = in_array('usediscount',$this->_options);
		$useTax = in_array('usetax',$this->_options);
		$useBase = in_array('usebase',$this->_options);

		$structuredItems=array();
	    $itemGroup=array();

		foreach($items as $item) {

			$weight=0;
			$qty=0;
			$price=0;
			$itemWeight=$item->getWeight();

			if (!Mage::helper('wsacommon/shipping')->getItemTotals($item,$weight,$qty,$price,$useParent,$this->_disablePromotions,
				$itemGroup,$useDiscount,$this->_freeShipping,$useBase,$useTax)) {
				continue;
			}

			$this->_runningCartPrice += $price;

            if ($item->getParentItem()!=null &&
                $useParent ) {
                // must be a bundle
                $product = $item->getParentItem()->getProduct();

            } else if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && !$useParent ) {
                if ($item->getHasChildren()) {
                    foreach ($item->getChildren() as $child) {
                        $product=$child->getProduct();
                        break;
                    }
                }
            } else {
                $product = $item->getProduct();
            }

			if (!is_object($product)) {
                $structuredItems[] = "";
				Mage::helper('wsalogger/log')->postCritical('shippingoverride2','Fatal Error','Item/Product is Malformed');
				break;
			}

			$specialShippingGroup = $product->getAttributeText('special_shipping_group');

			if (empty($specialShippingGroup)) { $specialShippingGroup='none'; }

			if (array_key_exists($specialShippingGroup,$structuredItems)) {
				// have already got this package id
				$structuredItems[$specialShippingGroup]['qty']=$structuredItems[$specialShippingGroup]['qty']+$qty;
				$structuredItems[$specialShippingGroup]['weight']=$structuredItems[$specialShippingGroup]['weight']+ $weight;
				if($structuredItems[$specialShippingGroup]['item_weight'] < $itemWeight) {
					$structuredItems[$specialShippingGroup]['item_weight']=$itemWeight;
				}
				$structuredItems[$specialShippingGroup]['price']=$structuredItems[$specialShippingGroup]['price']+ $price;
                if(!$this->_productRateInstalled) {
				    $structuredItems[$specialShippingGroup]['shipping_price']+=$product->getData('shipping_price')*$qty;
                }
				$structuredItems[$specialShippingGroup]['item_group']=array_merge($itemGroup,$structuredItems[$specialShippingGroup]['item_group']);
				$structuredItems[$specialShippingGroup]['unique']+=1;
			} else {
				$prodArray=array(
						  'qty' 			=> $qty,
						  'weight'			=> $weight,
						  'item_weight'		=> $itemWeight,
						  'price'			=> $price,
						  'shipping_price'	=> $this->_productRateInstalled ? 0 : $product->getData('shipping_price')*$qty,
						  'item_group'		=> $itemGroup,
						  'unique'			=> 1);
				$structuredItems[$specialShippingGroup]=$prodArray;
			}


			// also add to include_all
			if ($this->_starIncludeAll) {
				if (array_key_exists('include_all',$structuredItems)) {
						$structuredItems['include_all']['qty']=$structuredItems['include_all']['qty']+$qty;
						$structuredItems['include_all']['weight']=$structuredItems['include_all']['weight']+ $weight;
						if ($structuredItems['include_all']['item_weight'] < $itemWeight)
						{
							$structuredItems['include_all']['item_weight'] = $itemWeight;
						}
						$structuredItems['include_all']['price']=$structuredItems['include_all']['price']+ $price;
						if (array_key_exists($specialShippingGroup, $structuredItems['include_all']['shipping_price_array'])) {
                            if(!$this->_productRateInstalled) {
                                $structuredItems['include_all']['shipping_price_array'][$specialShippingGroup]+=
                                    $product->getData('shipping_price')*$qty;
                            }
						} else {
                            if(!$this->_productRateInstalled) {
							    $structuredItems['include_all']['shipping_price_array']+=array($specialShippingGroup=>$product->getData('shipping_price')*$qty);
                            }
						}
						$structuredItems['include_all']['item_group']=array_merge($itemGroup,$structuredItems['include_all']['item_group']);
						$structuredItems['include_all']['unique']+=1;
				} else {
					$prodArray=array(
					  'qty' 				=> $qty,
					  'weight'				=> $weight,
					  'item_weight'			=> $itemWeight,
					  'price'				=> $price,
				      'shipping_price_array'=> $this->_productRateInstalled ? 0 : array($specialShippingGroup=>$product->getData('shipping_price')*$qty),
					  'item_group'			=> $itemGroup,
					  'unique'				=> 1);
					$structuredItems['include_all']=$prodArray;
				}
			}
			$itemGroup=array();

		}

		if ($this->_debug) {

			foreach ($structuredItems as $key=>$structItem) {
				Mage::helper('wsalogger/log')->postDebug('shippingoverride2','Structured Item:',$key.', Qty:'.$structItem['qty'].
					', Weight:'.$structItem['weight'].', Price:'.$structItem['price'].', Item Weight:'.$structItem['item_weight'].
						', Item Group Count:'.count($structItem['item_group']));

				if (array_key_exists('shipping_price', $structItem)) {
					Mage::helper('wsalogger/log')->postDebug('shippingoverride2','shipping price',$structItem['shipping_price']);
				} else if (array_key_exists('shipping_price_array', $structItem)) {
					Mage::helper('wsalogger/log')->postDebug('shippingoverride2','shipping price',$structItem['shipping_price_array']);
				}

			}
		}

		return $structuredItems;
	}


	/**
	 * CSV Import routine
	 * @param $object
	 * @return unknown_type
	 */
    public function uploadAndImport(Varien_Object $object)
    {
        $csvFile = $_FILES["groups"]["tmp_name"]["shippingoverride2"]["fields"]["import"]["value"];
        $csvName = $_FILES["groups"]["name"]["shippingoverride2"]["fields"]["import"]["value"];
		$session = Mage::getSingleton('adminhtml/session');

        if (!empty($csvFile)) {

            $csv = trim(file_get_contents($csvFile));

            $table = Mage::getSingleton('core/resource')->getTableName('shippingoverride2/shippingoverride2');

            $websiteId = $object->getScopeId();

            Mage::helper('wsacommon/shipping')->saveCSV($csv,$csvName,$websiteId,'shippingoverride2');

            if (!empty($csv)) {
                $exceptions = array();
                $csvLines = explode("\n", $csv);
                $csvLine = array_shift($csvLines);
                $csvLine = $this->_getCsvValues($csvLine);
                if (count($csvLine) < 17) {
                    $exceptions[0] = Mage::helper('shipping')->__('Invalid Shipping Override File Format');
                }

                $countryCodes = array();
                $regionCodes = array();
                foreach ($csvLines as $k=>$csvLine) {
                    $csvLine = $this->_getCsvValues($csvLine);
                    if (count($csvLine) > 0 && count($csvLine) < 17) {
                        $exceptions[0] = Mage::helper('shipping')->__('Invalid Shipping Override File Format');
                    } else {
                        $splitCountries = explode(",", trim($csvLine[0]));
                    	$splitRegions = explode(",", trim($csvLine[1]));
                        foreach ($splitCountries as $country) {
                        	$countryCodes[] = trim($country);
                    	}
						foreach ($splitRegions as $region) {
                        	$regionCodes[] = $region;
                    	}
                    }
                }


                if (empty($exceptions)) {
                    $data = array();
                    $countryCodesToIds = array();
                    $regionCodesToIds = array();
                    $countryCodesIso2 = array();

                    $countryCollection = Mage::getResourceModel('directory/country_collection')->addCountryCodeFilter($countryCodes)->load();
                    foreach ($countryCollection->getItems() as $country) {
                        $countryCodesToIds[$country->getData('iso3_code')] = $country->getData('country_id');
                        $countryCodesToIds[$country->getData('iso2_code')] = $country->getData('country_id');
                        $countryCodesIso2[] = $country->getData('iso2_code');
                    }

                    $regionCollection = Mage::getResourceModel('directory/region_collection')
                        ->addRegionCodeFilter($regionCodes)
                        ->addCountryFilter($countryCodesIso2)
                        ->load();


                    foreach ($regionCollection->getItems() as $region) {
                        $regionCodesToIds[$countryCodesToIds[$region->getData('country_id')]][$region->getData('code')] = $region->getData('region_id');
                    }

                    foreach ($csvLines as $k=>$csvLine) {
                        $csvLine = $this->_getCsvValues($csvLine);
                        $splitCountries = explode(",", trim($csvLine[0]));
                        $splitPostcodes = explode(",", trim($csvLine[3]));
                        $splitRegions = explode(",", trim($csvLine[1]));
                        $customerGroups = explode(",",trim($csvLine[12]));

						if ($csvLine[2] == '*' || $csvLine[2] == '') {
							$city = '';
						} else {
							$city = $csvLine[2];
						}

						if ($csvLine[4] == '*' || $csvLine[4] == '') {
							$zip_to = '';
						} else {
							$zip_to = $csvLine[4];
						}


						if ($csvLine[5] == '*' || $csvLine[5] == '') {
							$special_shipping_group = '';
						} else {
							$special_shipping_group = $csvLine[5];
						}

                    	if ( $csvLine[6] == '*' || $csvLine[6] == '') {
							$weight_from = -1;
						} else if (!is_numeric($csvLine[6])) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid weight From "%s" in the Row #%s', $csvLine[6], ($k+1));
                    	} else {
							$weight_from = (float)$csvLine[6];
						}

						if ( $csvLine[7] == '*' || $csvLine[7] == '') {
							$weight_to = 10000000;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[7])) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid weight To "%s" in the Row #%s', $csvLine[7], ($k+1));
						}
						else {
							$weight_to = (float)$csvLine[7];
						}

						if ( $csvLine[8] == '*' || $csvLine[8] == '') {
							$price_from = -1;
						} else if (!is_numeric($csvLine[8]) ) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid price From "%s" in the Row #%s',  $csvLine[8], ($k+1));
						} else {
							$price_from = (float)$csvLine[8];
						}

						if ( $csvLine[9] == '*' || $csvLine[9] == '') {
							$price_to = 10000000;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[9])) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid price To "%s" in the Row #%s', $csvLine[9], ($k+1));
						} else {
							$price_to = (float)$csvLine[9];
						}

						if ( $csvLine[10] == '*' || $csvLine[10] == '') {
							$item_from = 0;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[10]) ) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid item From "%s" in the Row #%s',  $csvLine[10], ($k+1));
						} else {
							$item_from = (float)$csvLine[10];
						}

						if ( $csvLine[11] == '*' || $csvLine[11] == '') {
							$item_to = 10000000;
						} else if (!$this->_isPositiveDecimalNumber($csvLine[11])) {
							$exceptions[] = Mage::helper('shipping')->__('Invalid item To "%s" in the Row #%s', $csvLine[11], ($k+1));
						} else {
							$item_to = (float)$csvLine[11];
						}


                         if ($csvLine[14] == '*' || $csvLine[14] == '') {
							$percentage = '0';
						} else {
							$percentage = $csvLine[14];
						}

						foreach ($customerGroups as $customer_group) {

	                    	if ($customer_group == '*') {
								$customer_group = '';
							}

							foreach ($splitCountries as $country) {

	                      		$country=trim($country);

	                        	if (empty($countryCodesToIds) || !array_key_exists($country, $countryCodesToIds)) {
		                        	$countryId = '0';
		                            if ($country != '*' && $country != '') {
		                                $exceptions[] = Mage::helper('shipping')->__('Invalid Country "%s" in the Row #%s', $country, ($k+1));
		                                break;
		                            }
		                        } else {
		                            $countryId = $countryCodesToIds[$country];
		                        }

	                        	foreach ($splitRegions as $region) {

	                        		if (!isset($countryCodesToIds[$country])
			                            || !isset($regionCodesToIds[$countryCodesToIds[$country]])
			                            || !array_key_exists($region, $regionCodesToIds[$countryCodesToIds[$country]])) {
			                            $regionId = '0';
				                        if ($region != '*' && $region != '') {
			                            	$exceptions[] = Mage::helper('shipping')->__('Invalid Region/State "%s" in the Row #%s', $region, ($k+1));
			                            	break;
			                            }
			                        } else {
			                            $regionId = $regionCodesToIds[$countryCodesToIds[$country]][$region];
			                        }


									foreach ($splitPostcodes as $postcode) {
										if ($postcode == '*' || $postcode == '') {
											$zip = '';
											$new_zip_to = '';
										} else {
											$zip_str = explode("-", $postcode);
											if(count($zip_str) != 2) {
												$zip = trim($postcode);
												if (ctype_digit($postcode) && trim($zip_to) == '') {
													$new_zip_to = trim($postcode);
												} else $new_zip_to = $zip_to;
											}
											else {
												$zip = trim($zip_str[0]);
												$new_zip_to = trim($zip_str[1]);
											}
										}
										if (count($csvLine)==17) {
											$data[] = array('website_id'=>$websiteId, 'dest_country_id'=>$countryId, 'dest_region_id'=>$regionId,
											'dest_city'=>$city, 'dest_zip'=>$zip, 'dest_zip_to'=>$new_zip_to,
											'special_shipping_group'=>$special_shipping_group,
											'weight_from_value'=>$weight_from,'weight_to_value'=>$weight_to,
											'price_from_value'=>$price_from,'price_to_value'=>$price_to,
											'item_from_value'=>$item_from,'item_to_value'=>$item_to,
											'customer_group'=>$customer_group,
											'price'=>$csvLine[13], 'percentage'=>$percentage, 'delivery_type'=>$csvLine[15], 'algorithm'=>$csvLine[16],'item_weight_from_value'=>-1);
										} else {
											if ( $csvLine[18] == '*' || $csvLine[18] == '') {
												$item_weight_from = -1;
											} else if (!$this->_isPositiveDecimalNumber($csvLine[18])) {
												$exceptions[] = Mage::helper('shipping')->__('Invalid weight From "%s" in the Row #%s', $csvLine[18], ($k+1));
					                    	} else {
												$item_weight_from = (float)$csvLine[18];
											}

											if ( $csvLine[19] == '*' || $csvLine[19] == '') {
												$item_weight_to = 10000000;
											} else if (!$this->_isPositiveDecimalNumber($csvLine[19])) {
												$exceptions[] = Mage::helper('shipping')->__('Invalid weight To "%s" in the Row #%s', $csvLine[19], ($k+1));
											} else {
												$item_weight_to = (float)$csvLine[19];
											}

											$data[] = array('website_id'=>$websiteId, 'dest_country_id'=>$countryId, 'dest_region_id'=>$regionId,
											'dest_city'=>$city, 'dest_zip'=>$zip, 'dest_zip_to'=>$new_zip_to,
											'special_shipping_group'=>$special_shipping_group,
											'weight_from_value'=>$weight_from,'weight_to_value'=>$weight_to,
											'price_from_value'=>$price_from,'price_to_value'=>$price_to,
											'item_from_value'=>$item_from,'item_to_value'=>$item_to,
											'customer_group'=>$customer_group,
											'price'=>$csvLine[13], 'percentage'=>$percentage, 'delivery_type'=>$csvLine[15], 'algorithm'=>$csvLine[16],
											'rules'=>$csvLine[17],'item_weight_from_value'=>$item_weight_from,'item_weight_to_value'=>$item_weight_to);
										}
										$dataDetails[] = array('country'=>$country, 'region'=>$region);
									}
	                        	}
							}
                        }
                    }
                }
                if (empty($exceptions)) {
                    $connection = $this->_getWriteAdapter();

                     $condition = array(
                        $connection->quoteInto('website_id = ?', $websiteId),
                    );
                    $connection->delete($table, $condition);


                    foreach($data as $k=>$dataLine) {
                        try {
                            $connection->insert($table, $dataLine);
                        } catch (Exception $e) {
                            $exceptions[] = $e;
                        }
                    }
                    Mage::helper('wsacommon/shipping')->updateStatus($session,count($data));
                }
                if (!empty($exceptions)) {
                    throw new Exception( "\n" . implode("\n", $exceptions) );
                }
            }
        }
    }

    private function _getCsvValues($string, $separator=",")
    {
        $elements = explode($separator, trim($string));
        for ($i = 0; $i < count($elements); $i++) {
            $nquotes = substr_count($elements[$i], '"');
            if ($nquotes %2 == 1) {
                for ($j = $i+1; $j < count($elements); $j++) {
                    if (substr_count($elements[$j], '"') > 0) {
                        // Put the quoted string's pieces back together again
                        array_splice($elements, $i, $j-$i+1, implode($separator, array_slice($elements, $i, $j-$i+1)));
                        break;
                    }
                }
            }
            if ($nquotes > 0) {
                // Remove first and last quotes, then merge pairs of quotes
                $qstr =& $elements[$i];
                $qstr = substr_replace($qstr, '', strpos($qstr, '"'), 1);
                $qstr = substr_replace($qstr, '', strrpos($qstr, '"'), 1);
                $qstr = str_replace('""', '"', $qstr);
            }
            $elements[$i] = trim($elements[$i]);
        }
        return $elements;
    }

    private function _isPositiveDecimalNumber($n)
    {
        return preg_match ("/^[0-9]+(\.[0-9]*)?$/", $n);
    }


}
