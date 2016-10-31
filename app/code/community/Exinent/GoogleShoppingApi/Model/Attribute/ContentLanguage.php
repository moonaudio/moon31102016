<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Content language attribute's model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_ContentLanguage extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
        $config = Mage::getSingleton('googleshoppingapi/config');
        $targetCountry = $config->getTargetCountry($product->getStoreId());
        $value = $config->getCountryInfo($targetCountry, 'language', $product->getStoreId());

        $shoppingProduct->setContentLanguage($value);

    }
}
