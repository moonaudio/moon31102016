<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Availability attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_Availability extends Exinent_GoogleShoppingApi_Model_Attribute_Default
{
    protected $_googleAvailabilityMap = array(
        0 => 'out of stock',
        1 => 'in stock'
    );

    /**
     * Set current attribute to entry (for specified product)
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Google_Service_ShoppingContent_Product $shoppingProduct
     * @return Google_Service_ShoppingContent_Product
     */
    public function convertAttribute($product, $shoppingProduct)
    {
        $value = $this->_googleAvailabilityMap[(int)$product->isSalable()];
        
        if($product->getTypeId() == "configurable") {
			$value = $this->_googleAvailabilityMap[1];
        }
        
        return $shoppingProduct->setAvailability($value);
    }
}
