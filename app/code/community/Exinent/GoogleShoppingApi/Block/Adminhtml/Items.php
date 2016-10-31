<?php
/**
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */

/**
 * Adminhtml Google Content Items Grids Container
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Block_Adminhtml_Items extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('googleshoppingapi/items.phtml');
    }

    /**
     * Preparing layout
     *
     * @return Exinent_GoogleShoppingApi_Block_Adminhtml_Items
     */
    protected function _prepareLayout()
    {
        $this->setChild('item', $this->getLayout()->createBlock('googleshoppingapi/adminhtml_items_item'));
        $this->setChild('product', $this->getLayout()->createBlock('googleshoppingapi/adminhtml_items_product'));
        $this->setChild('store_switcher', $this->getLayout()->createBlock('googleshoppingapi/adminhtml_store_switcher'));

        return $this;
    }

    /**
     * Get HTML code for Store Switcher select
     *
     * @return string
     */
    public function getStoreSwitcherHtml()
    {
        return $this->getChildHtml('store_switcher');
    }

    /**
     * Get HTML code for CAPTCHA
     *
     * @return string
     */
    public function getCaptchaHtml()
    {
        return $this->getLayout()->createBlock('googleshoppingapi/adminhtml_captcha')
            ->setGcontentCaptchaToken($this->getGcontentCaptchaToken())
            ->setGcontentCaptchaUrl($this->getGcontentCaptchaUrl())
            ->toHtml();
    }

    /**
     * Get selecetd store
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return $this->_getData('store');
    }

    /**
     * Check whether synchronization process is running
     *
     * @return bool
     */
    public function isProcessRunning()
    {
        $flag = Mage::getModel('googleshoppingapi/flag')->loadSelf();
        return $flag->isLocked();
    }

    /**
     * Build url for retrieving background process status
     *
     * @return string
     */
    public function getStatusUrl()
    {
        return $this->getUrl('*/*/status');
    }
}
