<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Link attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_Link extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
        $url = $product->getProductUrl(false);
        if ($url) {
            if (!Mage::getStoreConfigFlag('web/url/use_store')) {
                $urlInfo = parse_url($url);
                $store = $product->getStore()->getCode();
                if (isset($urlInfo['query']) && $urlInfo['query'] != '') {
                    $url .= '&___store=' . $store;
                } else {
                    $url .= '?___store=' . $store;
                }
            }
            
            $config = Mage::getSingleton('googleshoppingapi/config');
			if( $config->getAddUtmSrcGshopping($product->getStoreId()) ) {
				$url .= '&utm_source=GoogleShopping';
			}
			if( $customUrlParameters = 
					$config->getCustomUrlParameters($product->getStoreId()) ) {
				$url .= $customUrlParameters;
			}
            
            $shoppingProduct->setLink($url);
        }

        return $shoppingProduct;
    }
}
