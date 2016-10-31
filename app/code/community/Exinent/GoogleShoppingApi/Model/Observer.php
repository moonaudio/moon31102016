<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Shopping Observer
 *
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Observer
{
    /**
     * Update product item in Google Content
     *
     * @param Varien_Object $observer
     * @return Exinent_GoogleShoppingApi_Model_Observer
     */
    public function saveProductItem($observer)
    {
        $product = $observer->getEvent()->getProduct();
        $items = $this->_getItemsCollection($product);

        try {
            Mage::getModel('googleshoppingapi/massOperations')
                ->synchronizeItems($items);
        } catch (Zend_Gdata_App_CaptchaRequiredException $e) {
            Mage::getSingleton('adminhtml/session')
                ->addError('Cannot update Google Content Item. Google requires CAPTCHA.');
        }

        return $this;
    }

    /**
     * Delete product item from Google Content
     *
     * @param Varien_Object $observer
     * @return Exinent_GoogleShoppingApi_Model_Observer
     */
    public function deleteProductItem($observer)
    {
        $product = $observer->getEvent()->getProduct();
        $items = $this->_getItemsCollection($product);

        try {
            Mage::getModel('googleshoppingapi/massOperations')
                ->deleteItems($items);
        } catch (Zend_Gdata_App_CaptchaRequiredException $e) {
            Mage::getSingleton('adminhtml/session')
                ->addError('Cannot delete Google Content Item. Google requires CAPTCHA.');
        }

        return $this;
    }

    /**
     * Get items which are available for update/delete when product is saved
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Exinent_GoogleShoppingApi_Model_Mysql4_Item_Collection
     */
    protected function _getItemsCollection($product)
    {
        $items = Mage::getResourceModel('googleshoppingapi/item_collection')
            ->addProductFilterId($product->getId());
        if ($product->getStoreId()) {
            $items->addStoreFilter($product->getStoreId());
        }

        foreach ($items as $item) {
            if (!Mage::getStoreConfigFlag('google/googleshoppingapi/observed', $item->getStoreId())) {
                $items->removeItemByKey($item->getId());
            }
        }

        return $items;
    }

    /**
     * Check if synchronize process is finished and generate notification message
     *
     * @param  Varien_Event_Observer $observer
     * @return Exinent_GoogleShoppingApi_Model_Observer
     */
    public function checkSynchronizationOperations(Varien_Event_Observer $observer)
    {
        $flag = Mage::getSingleton('googleshoppingapi/flag')->loadSelf();
        if ($flag->isExpired()) {
            Mage::getModel('adminnotification/inbox')->addMajor(
                Mage::helper('googleshoppingapi')->__('Google Shopping operation has expired.'),
                Mage::helper('googleshoppingapi')->__('One or more google shopping synchronization operations failed because of timeout.')
            );
            $flag->unlock();
        }
        return $this;
    }
    public function deleteexpirproducts() {
        Mage::log('cron_started',1,'exinentgoogleshopping.log');
        $flag = Mage::getSingleton('googleshoppingapi/flag')->load(13);
        $flag->setState('0')->save();
        $itemsCollection = Mage::getResourceModel('googleshoppingapi/item_collection');
        foreach ($itemsCollection as $items) {
            $date1 = date('Y-m-d');
            $date2 = $items->getExpires();
            $time = strtotime($date2);
            $newformat = date('Y-m-d', $time);
            if ($date1 > $newformat) {
                Mage::log($items->getName().' was deleted successfully',1,'exinentgoogleshopping.log');
                $items->delete();
            }
        }
        Mage::log('cron_ended',1,'exinentgoogleshopping.log');
    }
}
