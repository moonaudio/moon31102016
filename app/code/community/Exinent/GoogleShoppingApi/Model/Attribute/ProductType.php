<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * ProductType attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_ProductType extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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

        $productCategories = $product->getCategoryIds();

        // TODO: set Default value for product_type attribute if product isn't assigned for any category
        $value = 'Shop';

        if (!empty($productCategories)) {
            $category = Mage::getModel('catalog/category')->load(
                array_shift($productCategories)
            );

            $breadcrumbs = array();

            foreach ($category->getParentCategories() as $cat) {
                $breadcrumbs[] = $cat->getName();
            }

            $value = implode(' > ', $breadcrumbs);
//             Mage::log($value);
        }

        $shoppingProduct->setProductType($value);
        return $shoppingProduct;
    }
}
