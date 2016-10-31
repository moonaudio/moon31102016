<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Data Api destination states
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Source_Destinationstates
{
    /**
     * Retrieve option array with destinations
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0,  'label' => Mage::helper('googleshoppingapi')->__('Default')),
            array('value' => 1, 'label' => Mage::helper('googleshoppingapi')->__('Required')),
            array('value' => 2, 'label' => Mage::helper('googleshoppingapi')->__('Excluded'))
        );
    }
}
