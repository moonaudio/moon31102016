<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Sipping weight attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_ShippingWeight extends Exinent_GoogleShoppingApi_Model_Attribute_Default
{
    /**
     * Default weight unit
     *
     * @var string
     */
    const WEIGHT_UNIT = 'kg';

    /**
     * Set current attribute to entry (for specified product)
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Google_Service_ShoppingContent_Product $shoppingProduct
     * @return Google_Service_ShoppingContent_Product
     */
    public function convertAttribute($product, $shoppingProduct)
    {
        $mapValue = $this->getProductAttributeValue($product);
        if (!$mapValue) {
            $weight = $this->getGroupAttributeWeight();
            $mapValue = $weight ? $weight->getProductAttributeValue($product) : null;
        }
		
        if ($mapValue) {
			$shippingWeight = new Google_Service_ShoppingContent_ProductShippingWeight();
			$shippingWeight->setValue($mapValue);
			$shippingWeight->setUnit(self::WEIGHT_UNIT);
			$shoppingProduct->setShippingWeight($shippingWeight);
        }

        return $shoppingProduct;
    }
}
