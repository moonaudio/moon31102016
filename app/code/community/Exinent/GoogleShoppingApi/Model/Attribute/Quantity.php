<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Quantity attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_Quantity extends Exinent_GoogleShoppingApi_Model_Attribute_Default
{
    /**
     * Set current attribute to entry (for specified product)
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Google_Service_ShoppingContent_Product $shoppingProduct
     * @return Google_Service_ShoppingContent_Product
     */
    public function convertAttribute($product, $shoppingProduct)
    {
    
		//TODO: qty hast to be set on inventory
//         $quantity = $product->getStockItem()->getQty();
//         if ($quantity) {
//             $value = $quantity ? max(1, (int) $quantity) : 1;
//             $this->_setAttribute($entry, 'quantity', self::ATTRIBUTE_TYPE_INT, $value);
//         }

        return $shoppingProduct;
    }
}
