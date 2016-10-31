<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * GoogleProductCategory attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_GoogleProductCategory extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
		// get category from product attribute
        $value = $product->getResource()->getAttribute('google_shopping_category')
        ->getFrontend()->getValue($product);
		$value = preg_replace('/\d+ /','',$value);
        $shoppingProduct->setGoogleProductCategory($value);
        
        return $shoppingProduct;
    }
}
