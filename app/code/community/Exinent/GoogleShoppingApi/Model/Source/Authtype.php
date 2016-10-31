<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Data Api authorization types Source
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Source_Authtype
{
    /**
     * Retrieve option array with authentification types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'authsub', 'label' => Mage::helper('googleshoppingapi')->__('AuthSub')),
            array('value' => 'clientlogin', 'label' => Mage::helper('googleshoppingapi')->__('ClientLogin'))
        );
    }
}
