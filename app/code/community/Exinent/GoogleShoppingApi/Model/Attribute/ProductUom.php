<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * ProductUom attribute model
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_ProductUom 
	extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
		$availableUnits = array(
			'mg','g','kg',
			'ml','cl','l','cbm',
			'cm','m',
			'sqm'
		);
    
		$basePriceAmount = $product->getBasePriceAmount();
		$basePriceUnit = strtolower($product->getBasePriceUnit());
		
		$unitPricingMeasure = $basePriceAmount .' '.$basePriceUnit;
		
		$basePriceReferenceAmount = $product->getBasePriceBaseAmount();
		$basePriceReferenceUnit = strtolower($product->getBasePriceBaseUnit());
		
		$unitPricingBaseMeasure = $basePriceReferenceAmount .' '.$basePriceReferenceUnit;

		// skip attribute if unit not available
		if(!in_array($basePriceUnit,$availableUnits) || !in_array($basePriceReferenceUnit,$availableUnits)) {
			return $shoppingProduct;
		}

		if(!empty($basePriceAmount) && !empty($basePriceReferenceAmount)) {
			
			$unitPricingMeasure = new Google_Service_ShoppingContent_ProductUnitPricingMeasure();
			$unitPricingMeasure->setUnit($basePriceUnit);
			$unitPricingMeasure->setValue($basePriceAmount);
			$unitPricingBaseMeasure = new Google_Service_ShoppingContent_ProductUnitPricingBaseMeasure();
			$unitPricingBaseMeasure->setUnit($basePriceReferenceUnit);
			$unitPricingBaseMeasure->setValue($basePriceReferenceAmount);
			
			
			$shoppingProduct->setUnitPricingMeasure($unitPricingMeasure);
			$shoppingProduct->setUnitPricingBaseMeasure($unitPricingBaseMeasure);
		}
		
		return $shoppingProduct;
        
    }
}
