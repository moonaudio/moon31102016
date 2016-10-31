<?php
/**
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */


/**
 * Adminhtml Google Content Item Type Country Renderer
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Block_Adminhtml_Types_Renderer_Country
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Renders Google Content Item Id
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $iso = $row->getData($this->getColumn()->getIndex());
        return Mage::getSingleton('googleshoppingapi/config')->getCountryInfo($iso, 'name');
    }
}
