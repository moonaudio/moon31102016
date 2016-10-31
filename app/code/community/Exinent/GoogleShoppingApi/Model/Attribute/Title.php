<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Title attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_Title extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
        $mapValue = $this->getProductAttributeValue($product);
        $name = $this->getGroupAttributeName();
        if (!is_null($name)) {
            $mapValue = $name->getProductAttributeValue($product);
        }

        if (!is_null($mapValue)) {
            $titleText = $mapValue;
        } elseif ($product->getName()) {
            $titleText = $product->getName();
        } else {
            $titleText = 'no title';
        }
        $titleText = Mage::helper('googleshoppingapi')->cleanAtomAttribute($titleText);
        
        $shoppingProduct->setTitle($titleText);

        return $shoppingProduct;
    }
}
