<?php

/**
 * Class RocketWeb_GoogleBaseFeedGenerator_Model_Product_Type_Price
 *
 * This is a wrapper over static methods from Mage_Catalog_Model_Product_Type_Price;
 * it improves testability by allowing stubs for these static methods
 * Use with Mage::getSingleton()
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Product_Type_Price
{

    public function calculatePrice($basePrice, $specialPrice, $specialPriceFrom, $specialPriceTo,
                                   $rulePrice = false, $wId = null, $gId = null, $productId = null) {
        return Mage_Catalog_Model_Product_Type_Price::calculatePrice(
            $basePrice, $specialPrice, $specialPriceFrom, $specialPriceTo, $rulePrice, $wId, $gId, $productId
        );
    }

    public function calculateSpecialPrice($finalPrice, $specialPrice, $specialPriceFrom, $specialPriceTo,
                                          $store = null) {
        return Mage_Catalog_Model_Product_Type_Price::calculateSpecialPrice(
            $finalPrice, $specialPrice, $specialPriceFrom, $specialPriceTo, $store
        );
    }

}