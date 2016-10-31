<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Control (destinations) attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_Destinations extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
        $destInfo = Mage::getSingleton('googleshoppingapi/config')
            ->getDestinationsInfo($product->getStoreId());

//         $shoppingProduct->setDestinationsMode($destInfo);
		// TODO: implement support for destinations
		//array(3) { ["ProductSearch"]=> NULL ["ProductAds"]=> NULL ["CommerceSearch"]=> NULL }

        return $shoppingProduct;
    }
}
