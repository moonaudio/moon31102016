<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Condition attribute's model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_Condition extends Exinent_GoogleShoppingApi_Model_Attribute_Default
{
    /**
     * Available condition values
     *
     * @var string
     */
    const CONDITION_NEW = 'new';
    const CONDITION_USED = 'used';
    const CONDITION_REFURBISHED = 'refurbished';

    /**
     * Set current attribute to entry (for specified product)
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Google_Service_ShoppingContent_Product $shoppingProduct
     * @return Google_Service_ShoppingContent_Product
     */
    public function convertAttribute($product, $shoppingProduct)
    {
        $availableConditions = array(
            self::CONDITION_NEW, self::CONDITION_USED, self::CONDITION_REFURBISHED
        );

        $mapValue = $this->getProductAttributeValue($product);
        if (!is_null($mapValue) && in_array($mapValue, $availableConditions)) {
            $condition = $mapValue;
        } else {
            $condition = self::CONDITION_NEW;
        }

        return $shoppingProduct->setCondition($condition);
    }
}
