<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Content Item statues Source
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Source_Statuses
{
    /**
     * Retrieve option array with Google Content item's statuses
     *
     * @return array
     */
    public function getStatuses()
    {
        return array(
            '0' => Mage::helper('googleshoppingapi')->__('Yes'),
            '1' => Mage::helper('googleshoppingapi')->__('No')
        );
    }
}
