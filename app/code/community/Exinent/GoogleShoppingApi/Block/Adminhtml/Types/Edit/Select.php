<?php
/**
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */

/**
 * Adminhtml Google Content attributes select block
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Block_Adminhtml_Types_Edit_Select extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('googleshoppingapi/types/edit/select.phtml');
    }

}
