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
class Webshopapps_Shippingoverride2_Model_Shipping_Shipping extends Mage_Shipping_Model_Shipping
{

    protected $_useRegularExpressions;
    protected $_request;
    // cloned request object
    protected $_override2Request;
    protected $_allItems;
    protected static $_debug;
    protected $_rateError;
    protected $_showError;
    protected $_options;
    protected $_freeMethods = array();
    protected $_origWeight;
    protected $_origFreeWeight;


    /**
     * Retrieve all methods for supplied shipping data
     *
     * @todo make it ordered
     * @param Mage_Shipping_Model_Shipping_Method_Request $data
     * @return Mage_Shipping_Model_Shipping
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {

        if ((!Mage::getStoreConfig('shipping/shippingoverride2/active') &&
            !Mage::getStoreConfig('carriers/wsafreightcommon/active')) || sizeof($request->getAllItems()) < 1
        ) {
            return parent::collectRates($request);
        }


        if (!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Freightrate', 'shipping/freightrate/active')) {
            if ($this->isFreightDisable($request)) {
                return $this;
            }
        }


        // dont use if TinyBrick is enabled, because they dont do magento correctly!
        if (Mage::helper('wsacommon')->isModuleEnabled('TinyBrick_OrderEdit')) {
            foreach ($request->getAllItems() as $item) {
                if (!is_object($item->getProduct())) {
                    return parent::collectRates($request);
                }
                break;
            }
        }

        $this->_options = explode(',', Mage::getStoreConfig("shipping/shippingoverride2/ship_options"));
        self::$_debug = Mage::helper('wsalogger')->isDebug('Webshopapps_Shippingoverride2');
        $freightName = '';
        $freightConfig = '';

        $this->setLimitCarrier($request);

        if (!Mage::getStoreConfig('shipping/shippingoverride2/active')) {
            return parent::collectRates($request);
        }

        if (!$request->getOrig()) {
            $request
                ->setCountryId(Mage::getStoreConfig('shipping/origin/country_id', $request->getStore()))
                ->setRegionId(Mage::getStoreConfig('shipping/origin/region_id', $request->getStore()))
                ->setCity(Mage::getStoreConfig('shipping/origin/city', $request->getStore()))
                ->setPostcode(Mage::getStoreConfig('shipping/origin/postcode', $request->getStore()));
        }

        $this->_request = $request;
        $this->_override2Request = clone $request;

        // else want to restrict what's sent to the carriers
        $override2ResourceModel = Mage::getResourceModel('shippingoverride2/shippingoverride2');
        $this->_useRegularExpressions = in_array('pattern', $this->_options);
        $exclusionList = array();
        $customError = "";
        $weightIncArr = array();

        $override2Groups = $override2ResourceModel->getNewRate($request, $exclusionList, $customError, $weightIncArr);
        $override = true;
        $resultSet = array();
        if (empty($override2Groups) && count($exclusionList) < 1) {
            $override = false;
        }

        $limitCarrier = $request->getLimitCarrier();
        $this->_showError = true;
        $this->_rateError = '';

        if (!Mage::helper('wsacommon')->checkItems('c2hpcHBpbmcvc2hpcHBpbmdvdmVycmlkZTIvc2hpcF9vbmNl',
            'Z3VtbXlob3VzZQ==', 'c2hpcHBpbmcvc2hpcHBpbmdvdmVycmlkZTIvc2VyaWFs')
        ) {
            Mage::helper('wsacommon/log')->postCritical('shippingoverride2', base64_decode('TGljZW5zZQ=='),
                    base64_decode('U2VyaWFsIEtleSBJbnZhbGlk'));
            return parent::collectRates($request);
        }


        if (!$limitCarrier) {
            $carriers = Mage::getStoreConfig('carriers', $request->getStoreId());

            foreach ($carriers as $carrierCode => $carrierConfig) {
                if ($override) {
                    $resultSet[] = $this->_collectOverrideRates($carrierCode, $request, $exclusionList,
                        $override2Groups, $customError, $freightName, $freightConfig, $weightIncArr);

                } else {
                    if (count($weightIncArr)>0) {
                        $this->_collectIncreasedWeightCarrierRates($carrierCode, $request,$weightIncArr);
                    } else {
                        $this->collectCarrierRates($carrierCode, $request);
                    }
                }
            }
        } else {
            if (!is_array($limitCarrier)) {
                $limitCarrier = array($limitCarrier);
            }
            foreach ($limitCarrier as $carrierCode) {
                $carrierConfig = Mage::getStoreConfig('carriers/' . $carrierCode, $request->getStoreId());
                if (!$carrierConfig) {
                    continue;
                }
                if ($override) {
                    $resultSet[] = $this->_collectOverrideRates($carrierCode, $request, $exclusionList,
                        $override2Groups, $customError, $freightName, $freightConfig,$weightIncArr);
                } else {
                    if (count($weightIncArr)>0) {
                        $this->_collectIncreasedWeightCarrierRates($carrierCode, $request,$weightIncArr);
                    } else {
                        $this->collectCarrierRates($carrierCode, $request);
                    }
                }
            }
        }
        $this->restrictResultSet($resultSet, $freightName);
        if (($this->_showError && Mage::getStoreConfig('shipping/shippingoverride2/show_method')
            || !Mage::helper('shippingoverride2')->getRatesExist() && Mage::getStoreConfig('shipping/shippingoverride2/show_method_no_rate')) &&
            $this->_rateError instanceof Mage_Shipping_Model_Rate_Result_Error
        ) {
            $errorResult = Mage::getModel('shipping/rate_result');
            $errorResult->append($this->_rateError);
            $this->getResult()->append($errorResult);
        }
        return $this;
    }


    protected function _collectIncreasedWeightCarrierRates($carrierCode,
        Mage_Shipping_Model_Rate_Request $request, $weightIncArr) {

        $this->_addWeightIncrease($request,$weightIncArr);

        $this->collectCarrierRates($carrierCode, $request);

        $this->_removeWeightIncrease($request,$weightIncArr);

    }

    protected function _addWeightIncrease(&$request,$weightIncArr) {

        if (array_key_exists('all_items',$weightIncArr)) {
            $this->_origWeight = $request->getPackageWeight();
            $this->_origFreeWeight = $request->getFreeMethodWeight();

            $request->setPackageWeight($this->_origWeight+$weightIncArr['all_items']);
            $request->setFreeMethodWeight($this->_origFreeWeight+$weightIncArr['all_items']);
        }
    }

    protected function _removeWeightIncrease(&$request,$weightIncArr) {

        if (array_key_exists('all_items',$weightIncArr)) {
            $request->setPackageWeight($this->_origWeight);
            $request->setFreeMethodWeight($this->_origFreeWeight);
        }
    }


    private function isFreightDisable(Mage_Shipping_Model_Rate_Request $request)
    {

        if (!Mage::getStoreConfig('carriers/freightrate/force_freight')) {
            // only works when wanting to completely disable freight rates
            return false;
        }

        $newRequest = clone $request;
        $freightModel = Mage::getModel('freightrate/freightrate');
        $nonFreightItemPresent = false;
        $freightFound = false;
        $exceedsMinOrder = false;

        if (Mage::helper('freightrate')->cartExceedsMinOrder($request->getPackageValueWithDiscount())) {
            $exceedsMinOrder = true;
        }

        if ($freightModel->getNonFreightItems($newRequest, $nonFreightItemPresent)) {
            $freightFound = true;
        }

        if (($freightFound || $exceedsMinOrder)) {
            $carrier = $this->getCarrierByCode("freightrate", $newRequest->getStoreId());
            $this->getResult()->append($carrier->collectFreightRates($newRequest));
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier("freightrate");
            $error->setCarrierTitle($carrier->getCarrierTitle());
            $error->setErrorMessage($freightModel->getDisclaimer());
            $this->getResult()->append($error);
            return true;
        }

        return false;
    }


    private function setLimitCarrier(&$request)
    {
        if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Wsafreightcommon')) {

            $limitCarrier = Mage::helper('wsafreightcommon')->limitCarriersBasedOnFreightRules($request,
                $request->getLimitCarrier());

            $request->setLimitCarrier($limitCarrier);
        }
    }

    public function collectCarrierRates($carrierCode, $request)
    {
        if (!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Handlingproduct', 'shipping/handlingproduct/active') &&
            !Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Insurance', 'shipping/insurance/active')
            && !Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Handlingmatrix', 'shipping/handlingmatrix/active')
        ) {
            return parent::collectCarrierRates($carrierCode, $request);
        }
        // dont use if TinyBrick is enabled, because they dont do magento correctly!
        if (Mage::helper('wsacommon')->isModuleEnabled('TinyBrick_OrderEdit')) {
            foreach ($request->getAllItems() as $item) {
                if (!is_object($item->getProduct())) {
                    return parent::collectRates($request);
                }
                break;
            }
        }

        $carrier = $this->getCarrierByCode($carrierCode, $request->getStoreId());
        if (!$carrier) {
            return $this;
        }
        $result = $carrier->checkAvailableShipCountries($request);
        if (false !== $result && !($result instanceof Mage_Shipping_Model_Rate_Result_Error)) {
            if (method_exists($carrier, 'proccessAdditionalValidation')) {
                $result = $carrier->proccessAdditionalValidation($request);
            }
        }
        /*
        * Result will be false if the admin set not to show the shipping module
        * if the devliery country is not within specific countries
        */
        if (false !== $result) {
            if (!$result instanceof Mage_Shipping_Model_Rate_Result_Error) {
                $result = $carrier->collectRates($request);
            }
            // sort rates by price
            if (method_exists($result, 'sortRatesByPrice')) {
                $result->sortRatesByPrice();
            }
            if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Handlingproduct', 'shipping/handlingproduct/active')) {
                $handlingModel = Mage::getModel('handlingproduct/handlingproduct');
                $handlingModel->addHandlingCosts($request, $result);
            }
            if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Handlingmatrix', 'shipping/handlingmatrix/active')) {
                $handlingMatrixModel = Mage::getModel('handlingmatrix/handlingmatrix');
                $handlingMatrixModel->addHandlingCosts($request, $result);
            }
            if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Insurance', 'shipping/insurance/active')) {
                $result = Mage::helper('insurance')->getInsuranceResults($request, $result,$carrierCode);
            }
            $this->getResult()->append($result);

        }
        return $this;
    }


    /**
     * Called from Dropship
     * Ignore limitCarrier/YRC as this is all managed inside Dropship module
     * @param unknown_type $origRequest
     * @param unknown_type $request
     * @param unknown_type $exclusionList
     * @param unknown_type $deliveryOverrideList
     * @param unknown_type $totalShipPrice
     * @return unknown
     */
    public function collectSpecialRates($origRequest, $request, $exclusionList, $deliveryOverrideList, $totalShipPrice,
                                        $customError, $freightName = '', $freightConfig = '')
    {

        $resultSet = array();

    	$this->_options = explode(',',Mage::getStoreConfig("shipping/shippingoverride2/ship_options"));
     	$this->_useRegularExpressions = in_array('pattern',$this->_options); // just reget this as may have been invoked via dropship


    	$limitCarrier = $request->getLimitCarrier();
    	$this->_override2Request=$origRequest;

        if (!$limitCarrier) {
            $carriers = Mage::getStoreConfig('carriers', $request->getStoreId());

            foreach ($carriers as $carrierCode => $carrierConfig) {

                $resultSet[] = $this->_collectOverrideRates($carrierCode, $request, $exclusionList,
                    $deliveryOverrideList, $totalShipPrice, $customError, $freightName, $freightConfig);
            }
        } else {
            if (!is_array($limitCarrier)) {
                $limitCarrier = array($limitCarrier);
            }
            foreach ($limitCarrier as $carrierCode) {
                $carrierConfig = Mage::getStoreConfig('carriers/' . $carrierCode, $request->getStoreId());
                if (!$carrierConfig) {
                    continue;
                }
                $resultSet[] = $this->_collectOverrideRates($carrierCode, $request, $exclusionList,
                    $deliveryOverrideList, $totalShipPrice, $customError, $freightName, $freightConfig);
            }
        }

        $this->restrictResultSet($resultSet, $freightName);
        return $this;
    }


    private function collectShippingRates($carrier, $request)
    {

        $result = $carrier->checkAvailableShipCountries($request);
        if (false !== $result && !($result instanceof Mage_Shipping_Model_Rate_Result_Error)) {
            if (method_exists($carrier, 'proccessAdditionalValidation')) {
                $result = $carrier->proccessAdditionalValidation($request);
            }
        }
        /*
        * Result will be false if the admin set not to show the shipping module
        * if the devliery country is not within specific countries
        */
        if (false !== $result) {
            if (!$result instanceof Mage_Shipping_Model_Rate_Result_Error) {
                $result = $carrier->collectRates($request);
            }
            // sort rates by price
            if (method_exists($result, 'sortRatesByPrice')) {
                $result->sortRatesByPrice();
            }
        }
        return $result;
    }

    protected function _collectOverrideRates($carrierCode, $request, $exclusionList,
                                          $deliveryOverrideList, $customError, $freightName = '', $freightConfig = '',
                                          $weightIncArr = array())
    {
        $specialResultsArry = array();

        $freeShippingText = Mage::getStoreConfig('shipping/shippingoverride2/free_shipping_text');
        $carrier = $this->getCarrierByCode($carrierCode, $request->getStoreId());
        if (!$carrier) {
            return;
        }

        $this->_allItems = $request->getAllItems();
        if (!empty($this->_allItems) && ($this->_allItems != "")) {
            $this->_addWeightIncrease($request,$weightIncArr);
            $allItemsResults = $this->collectShippingRates($carrier, $request);
            $this->_removeWeightIncrease($request,$weightIncArr);
        }
        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Override Results', $allItemsResults);
        }
        foreach ($deliveryOverrideList as $deliveryType => $override2Details) {
            $this->_override2Request->setChargeLiftgateOnly(false);
            if ($override2Details['whole_cart'] || !array_key_exists('cart_details', $override2Details)) {

                if (($deliveryOverrideList[$deliveryType]['whole_cart']) &&
                    array_key_exists('surcharge_liftgate',$deliveryOverrideList[$deliveryType]) &&
                    $deliveryOverrideList[$deliveryType]['surcharge_liftgate']) {
                    // we have a straight override on the whole cart, but still want to add the liftgate charge
                    $request->setChargeLiftgateOnly(true);
                    $this->_addWeightIncrease($request,$weightIncArr);
                    $deliveryOverrideList[$deliveryType]['group_results'] = $this->collectShippingRates($carrier,$request);
                    $this->_removeWeightIncrease($request,$weightIncArr);
                    $request->setChargeLiftgateOnly(false);
                }
                if (self::$_debug) {
                    Mage::helper('wsalogger/log')->postDebug('shippingoverride2',
                            'Overriding Delivery Type', $deliveryType);
                }
                continue;
            }
            if (Mage::helper('wsacommon/shipping')->hasFreightCarrierEnabled() && $carrierCode == $freightName
                && $deliveryType == Mage::getStoreConfig($freightConfig . 'name') &&
                $override2Details['cart_details']['weight'] < Mage::getStoreConfig($freightConfig . 'min_weight')
            ) {
                if (self::$_debug) {
                    Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Exiting as weight not met', $override2Details['cart_details']['weight']);
                }
                return array();
            }


            $weightToAdd = $override2Details['cart_details']['weight'] +
                    $override2Details['weight_increase'];

            $this->_override2Request->setPackageValue($override2Details['cart_details']['price']);
            $this->_override2Request->setAllItems($override2Details['cart_details']['item_group']);
            $this->_override2Request->setPackageValueWithDiscount($override2Details['cart_details']['price']);
            $this->_override2Request->setPackageWeight($weightToAdd);
            $this->_override2Request->setFreeMethodWeight($weightToAdd);
            $this->_override2Request->setPackageQty($override2Details['cart_details']['qty']);

            $deliveryOverrideList[$deliveryType]['group_results'] = $this->collectShippingRates($carrier, $this->_override2Request);
            if (self::$_debug) {
                Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Override Delivery Type', $deliveryType);
                Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Override List', $deliveryOverrideList[$deliveryType]['group_results']);
                Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Weight', $weightToAdd);
                Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Price', $override2Details['cart_details']['price']);
                Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Item Group Count', count($override2Details['cart_details']['item_group']));

            }
        }


        if (!empty($allItemsResults) &&
            is_array($allItemsResults->getAllRates()) && count($allItemsResults->getAllRates())
        ) {
            $rates = $allItemsResults->getAllRates();
            $saveRates = $allItemsResults->getAllRates();


            foreach ($rates as $key => $rate) {
                $methodTitle = trim($rate->getMethodTitle());
                if (in_array($methodTitle, $exclusionList)) {
                    $rates[$key] = "";
                    continue;
                }
                if ($rate instanceof Mage_Shipping_Model_Rate_Result_Error) {
                    $rates[$key] = "";
                    break;
                }
                if ($this->_useRegularExpressions) {
                    $found = false;
                    foreach ($exclusionList as $excludedMethodName) {
                        if (preg_match("/" . $excludedMethodName . "/", $methodTitle)) {
                            $rates[$key] = "";
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        continue;
                    }
                }

                $wipeRate = true;
                $percentageRate = -99;
                $regMatched = false;
                $rates[$key]->setNewPrice(0);
                $found = false;
                $overrideWipe = false;
                foreach ($deliveryOverrideList as $deliveryType => $override2Details) {
                    try {
                        if ($this->_useRegularExpressions && preg_match("/" . $deliveryType . "/", $methodTitle)) {
                            $regMatched = true;
                        }
                    } catch (Exception $e) {

                    }
                    if ($regMatched || (trim($rate->getMethodTitle()) == $deliveryType)) {
                        $found = true;
                        if (self::$_debug) {
                            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Matched On', $deliveryType);
                            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'ship price', $override2Details['ship_price']);
                            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'ship percent', $override2Details['ship_percent']);
                        }

                        $rates[$key]->setNewPrice($rates[$key]->getNewPrice() +
                            $override2Details['product_ship_price'] + $override2Details['ship_price']);
                        $override2Details['product_ship_price'] = 0; // cant be added again
                        if (!empty($override2Details['ship_percent']) && is_numeric($override2Details['ship_percent'])
                            && $override2Details['ship_percent'] > $percentageRate
                        ) {
                            $percentageRate = $override2Details['ship_percent'];
                        } // see ahume jyoo alana here  last edit remove else


                        if (array_key_exists('group_results', $override2Details) && !$override2Details['override']) {
                            if (self::$_debug) {
                                Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'in here group results', '');
                            }
                            if (!$override2Details['wipe_rate']) {
                                $newPrice = $this->getNewRateForType($override2Details['group_results'], $rates[$key]->getMethod());
                                if (is_numeric($newPrice) && $newPrice > 0) {
                                    $rates[$key]->setNewPrice($rates[$key]->getNewPrice() + $newPrice);
                                    $wipeRate = true;
                                }
                            } else {
                                $newPrice = $this->getNewRateForType($override2Details['group_results'], $rates[$key]->getMethod());
                                if (is_numeric($newPrice) && $newPrice > 0) {
                                    $rates[$key]->setNewPrice($rates[$key]->getNewPrice() + $newPrice);
                                    if (!$override2Details['wipe_rate'] && !$overrideWipe) {
                                        $wipeRate = false;
                                    }
                                }

                            }
                        } else
                            if (!$override2Details['wipe_rate'] && !$overrideWipe) {
                                $wipeRate = false;
                            }
                        break;
                    }
                }
                if ($found && $wipeRate && !empty($rates[$key])) {
                    if (self::$_debug) {
                        Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'wiping rate', $rate->getMethodTitle());
                        Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'new price:', $rates[$key]->getNewPrice());
                        Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'price:', $rates[$key]->getPrice());
                    }
                    if ($percentageRate != -99 && $rates[$key]->getNewPrice() > 0) {
                        if ($rates[$key]->getNewPrice() == 0) {
                            $this->_freeMethods[$rates[$key]->getMethod()] = $rates[$key]->getPrice();
                        }
                        if (self::$_debug) {
                            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'percentage', $percentageRate);
                        }
                        $rates[$key]->setPrice($rates[$key]->getNewPrice() * (1 + $percentageRate / 100));
                    } else {
                        if ($rates[$key]->getNewPrice() == 0) {
                            $this->_freeMethods[$rates[$key]->getMethod()] = $rates[$key]->getPrice();
                        }
                        $rates[$key]->setPrice($rates[$key]->getNewPrice());
                    }
                    if ($rates[$key]->getNewPrice() == 0 && $freeShippingText != "") {
                        $rates[$key]->setMethodTitle($freeShippingText);
                    }
                    //$rates[$key]->setPrice($rates[$key]->getPrice()+$totalShipPrice);
                } else if ($found && $rates[$key] != "") {
                    if (self::$_debug) {
                        Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'not wiping rate', $rate->getMethodTitle());
                        Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'new price:', $rates[$key]->getNewPrice());
                        Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'price:', $rates[$key]->getPrice());
                    }
                    if ($percentageRate != -99) {
                        if (self::$_debug) {
                            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Method being manipulated', $rates[$key]->getMethodTitle());
                            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Percentage being applied', $percentageRate);
                            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'New Price', $rates[$key]->getNewPrice());
                            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Original Price', $rates[$key]->getPrice());
                        }
                        $rates[$key]->setPrice(($rates[$key]->getPrice() * (1 + $percentageRate / 100)) + $rates[$key]->getNewPrice());

                    } else {
                        $rates[$key]->setPrice($rates[$key]->getPrice() + $rates[$key]->getNewPrice());
                    }
                }
            }

            $allItemsResults->reset();

            $found = false;

            foreach ($rates as $key => $rate) {
                if (!empty($rates[$key])) {
                    if (count($this->_freeMethods) > 0) {
                        $rate->setOverridePriceInfo($this->saveFreeShippingAmount());
                    }
                    $allItemsResults->append($rate);
                    $found = true;
                }
            }

            Mage::helper('shippingoverride2')->setRatesExist($found);
            if (!$found) {
                $rate = $saveRates[0];
                if ($this->_showError && Mage::getStoreConfig('shipping/shippingoverride2/show_method') || !Mage::helper('shippingoverride2')->getRatesExist() && Mage::getStoreConfig('shipping/shippingoverride2/show_method_no_rate')) {
                    $this->_rateError = Mage::getModel('shipping/rate_result_error');
                    $this->_rateError->setCarrier($rate->getCarrier());
                    $this->_rateError->setCarrierTitle($rate->getCarrierTitle());
                    if (empty($customError)) {
                        $this->_rateError->setErrorMessage(Mage::getStoreConfig('shipping/shippingoverride2/specificerrmsg'));
                    } else {
                        $this->_rateError->setErrorMessage($customError);
                    }
                }
            } else {
                if (method_exists($allItemsResults, 'sortRatesByPrice')) {
                    $allItemsResults->sortRatesByPrice();
                }
            }

            if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Handlingproduct', 'shipping/handlingproduct/active')) {
                $handlingModel = Mage::getModel('handlingproduct/handlingproduct');

                $handlingModel->addHandlingCosts($request, $allItemsResults);

            }
            if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Handlingmatrix', 'shipping/handlingmatrix/active')) {
                $handlingMatrixModel = Mage::getModel('handlingmatrix/handlingmatrix');
                $handlingMatrixModel->addHandlingCosts($request, $allItemsResults);
            }
            if (Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Insurance', 'shipping/insurance/active')) {
                $specialResultsArry[] = Mage::helper('insurance')->getInsuranceResults($request, $allItemsResults,$carrierCode);
            } else {
                $specialResultsArry[] = $allItemsResults;
            }
        }

        return $specialResultsArry;

    }


    private function restrictResultSet($specialResultsArry)
    {
        if (empty($specialResultsArry) || !is_array($specialResultsArry)) {
            return;
        }

        $allItemsResults = Mage::getModel('shipping/rate_result');

        $foundRL = false;
        $foundNonRL = false;
        $foundFree = false;
        $restrictRates = Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Yrcfreight', 'carriers/yrcfreight/active') &&
            Mage::getStoreConfig('carriers/yrcfreight/restrict_rates');
        // restrict results to just freight if valid
        if ($restrictRates) {
            foreach ($specialResultsArry as $outerArray) {
                if (is_array($outerArray)) {
                    foreach ($outerArray as $newItemsResults) {
                        $rates = $newItemsResults->getAllRates();
                        foreach ($rates as $key => $rate) {
                            if (!($rate instanceof Mage_Shipping_Model_Rate_Result_Error)) {
                                if ($rate->getCarrier() != 'yrcfreight') {
                                    $foundNonRL = true;
                                    if ($rate->getPrice() == 0 && $rate->getCarrier() == 'ups') {
                                        $foundFree = true;
                                    }
                                } else {
                                    $foundRL = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($specialResultsArry as $outerArray) {
            if (is_array($outerArray)) {
                foreach ($outerArray as $newItemsResults) {
                    $rates = $newItemsResults->getAllRates();
                    if ($restrictRates && $foundRL && $foundNonRL) {
                        $allItemsResults->reset();
                        if ($foundFree) {
                            foreach ($rates as $key => $rate) {
                                if (!($rate instanceof Mage_Shipping_Model_Rate_Result_Error) && $rate->getCarrier() == 'ups') {
                                    $allItemsResults->append($rate);
                                }
                            }
                        } else {
                            foreach ($rates as $rate) {
                                if ($foundNonRL && !($rate instanceof Mage_Shipping_Model_Rate_Result_Error) && $rate->getCarrier() == 'yrcfreight') {
                                    $allItemsResults->append($rate);
                                }
                            }
                        }
                        $this->getResult()->append($allItemsResults);
                    } else {
                        $this->getResult()->append($newItemsResults);
                    }
                }
            }
        }

    }

    private function getNewRateForType($override2Results, $method)
    {

        $shipPrice = 0;

        if (!empty($override2Results) &&
            is_array($override2Results->getAllRates()) && count($override2Results->getAllRates())
        ) {
            $rates = $override2Results->getAllRates();

            if (($method == 'FEDEX_GROUND') && (Mage::getStoreConfig('shipping/shippingoverride2/fedex_ground'))) {
                $method = 'GROUND_HOME_DELIVERY';
            }

            foreach ($rates as $key => $rate) {
                if ($rate->getMethod() == $method) {
                    $shipPrice = $rate->getPrice();
                }
            }
        }

        return $shipPrice;
    }

    private function saveFreeShippingAmount()
    {
        $freeData = Zend_Json::encode($this->_freeMethods);

        if (self::$_debug) {
            Mage::helper('wsalogger/log')->postDebug('shippingoverride2', 'Free Shipping Differences', $freeData);
        }

        return $freeData;
    }
}
