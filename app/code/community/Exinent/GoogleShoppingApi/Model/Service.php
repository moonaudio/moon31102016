<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Content Item Types Model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Service extends Varien_Object
{

    /**
     * Return Google Content Service Instance
     *
     * @param int $storeId
     * @return Exinent_GoogleShoppingApi_Model_GoogleShopping
     */
    public function getService($storeId = null)
    {
        if (!$this->_service) {
            $this->_service = Mage::getModel('googleshoppingapi/googleShopping');

//             if ($this->getConfig()->getIsDebug($storeId)) {
//                 $this->_service
//                     ->setLogAdapter(Mage::getModel('core/log_adapter', 'googleshoppingapi.log'), 'log')
//                     ->setDebug(true);
//             }
        }
        return $this->_service;
    }

    /**
     * Set Google Content Service Instance
     *
     * @param Exinent_GoogleShoppingApi_Model_GoogleShopping $service
     * @return Exinent_GoogleShoppingApi_Model_Service
     */
    public function setService($service)
    {
        $this->_service = $service;
        return $this;
    }

    /**
     * Google Content Config
     *
     * @return Exinent_GoogleShoppingApi_Model_Config
     */
    public function getConfig()
    {
        return Mage::getSingleton('googleshoppingapi/config');
    }

}
