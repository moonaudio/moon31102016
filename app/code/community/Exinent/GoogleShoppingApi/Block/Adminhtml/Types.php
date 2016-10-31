<?php
/**
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */

/**
 * Adminhtml Google Contyent Item Types Grid
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */

class Exinent_GoogleShoppingApi_Block_Adminhtml_Types extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'googleshoppingapi';
        $this->_controller = 'adminhtml_types';
        $this->_addButtonLabel = Mage::helper('googleshoppingapi')->__('Add Attribute Mapping');
        $this->_headerText = Mage::helper('googleshoppingapi')->__('Manage Attribute Mapping');
        parent::__construct();
    }
}
