<?php
/**
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */

/**
 * Adminhtml GoogleShopping Store Switcher
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Block_Adminhtml_Store_Switcher extends Mage_Adminhtml_Block_Store_Switcher
{
    /**
     * Whether the switcher should show default option
     *
     * @var bool
     */
    protected $_hasDefaultOption = false;

    /**
     * Set overriden params
     */
    public function __construct()
    {
        parent::__construct();
        $this->setUseConfirm(false)->setSwitchUrl($this->getUrl('*/*/*', array('store' => null)));
    }
}
