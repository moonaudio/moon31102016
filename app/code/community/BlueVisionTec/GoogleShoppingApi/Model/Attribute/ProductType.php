<?php
/**
 * @category	BlueVisionTec
 * @package     BlueVisionTec_GoogleShoppingApi
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @copyright   Copyright (c) 2015 BlueVisionTec UG (haftungsbeschränkt) (http://www.bluevisiontec.de)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * ProductType attribute model
 *
 * @category	BlueVisionTec
 * @package    BlueVisionTec_GoogleShoppingApi
 * @author     Magento Core Team <core@magentocommerce.com>
 * @author      BlueVisionTec UG (haftungsbeschränkt) <magedev@bluevisiontec.eu>
 */
class BlueVisionTec_GoogleShoppingApi_Model_Attribute_ProductType extends BlueVisionTec_GoogleShoppingApi_Model_Attribute_Default
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
            
            if(!count($breadcrumbs)) {
               $value = $category->getName();
            }
        }

        $shoppingProduct->setProductType($value);
        return $shoppingProduct;
    }
}
