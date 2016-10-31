<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Image link attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_ImageLink extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
        $url = $product->getGoogleShoppingImage();
        if($url && $url != "no_selection") {
        
            $url = Mage::getModel('catalog/product_media_config')->getMediaUrl($product->getGoogleShoppingImage());
            $shoppingProduct->setImageLink($url);
            return $shoppingProduct;
        }
        
        $url = Mage::helper('catalog/product')->getImageUrl($product);

        if ($product->getImage() && $product->getImage() != 'no_selection' && $url) {
           $shoppingProduct->setImageLink($url);
        }
        return $shoppingProduct;
    }
}
