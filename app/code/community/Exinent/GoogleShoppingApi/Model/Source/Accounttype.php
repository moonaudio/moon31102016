<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Data Api account types Source
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Source_Accounttype
{
    /**
     * Retrieve option array with account types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'HOSTED_OR_GOOGLE', 'label' => Mage::helper('googleshoppingapi')->__('Hosted or Google')),
            array('value' => 'GOOGLE', 'label' => Mage::helper('googleshoppingapi')->__('Google')),
            array('value' => 'HOSTED', 'label' => Mage::helper('googleshoppingapi')->__('Hosted'))
        );
    }
}
