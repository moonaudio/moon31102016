<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Id attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_Id extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
        $value = Mage::helper('googleshoppingapi')->buildContentProductId($product->getId(), $product->getStoreId());
        return $shoppingProduct->setOfferId($value);
    }
}
