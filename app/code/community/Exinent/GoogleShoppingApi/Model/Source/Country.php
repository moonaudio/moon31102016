<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Google Content Target country Source
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Source_Country
{
    /**
     * Retrieve option array with allowed countries
     *
     * @return array
     */
    public function toOptionArray()
    {
        $_allowed = Mage::getSingleton('googleshoppingapi/config')->getAllowedCountries();
        $result = array();
        foreach ($_allowed as $iso => $info) {
            $result[] = array('value' => $iso, 'label' => $info['name']);
        }
        return $result;
    }
}
