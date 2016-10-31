<?php
/**
 * @category	Exinent
 * @package     Exinent_GoogleShoppingApi
 */

/**
 * Sale price effective date attribute model.
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Model_Attribute_SalePriceEffectiveDate extends Exinent_GoogleShoppingApi_Model_Attribute_Default
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
        $effectiveDateFrom = $this->getGroupAttributeSalePriceEffectiveDateFrom();
        $fromValue = $effectiveDateFrom->getProductAttributeValue($product);

        $effectiveDateTo = $this->getGroupAttributeSalePriceEffectiveDateTo();
        $toValue = $effectiveDateTo->getProductAttributeValue($product);

        $from = $to = null;
        if (!empty($fromValue) && Zend_Date::isDate($fromValue, Zend_Date::ATOM)) {
            $from = new Zend_Date($fromValue, Zend_Date::ATOM);
        }
        if (!empty($toValue) && Zend_Date::isDate($toValue, Zend_Date::ATOM)) {
            $to = new Zend_Date($toValue, Zend_Date::ATOM);
        }

        $dateString = null;
        // if we have from an to dates, and if these dates are correct
        if (!is_null($from) && !is_null($to) && $from->isEarlier($to)) {
            $dateString = $from->toString(Zend_Date::ATOM) . '/' . $to->toString(Zend_Date::ATOM);
        }

        // if we have only "from" date, send "from" day
        if (!is_null($from) && is_null($to)) {
            $dateString = $from->toString('YYYY-MM-dd');
        }

        // if we have only "to" date, use "now" date for "from"
        if (is_null($from) && !is_null($to)) {
            $from = new Zend_Date();
            // if "now" date is earlier than "to" date
            if ($from->isEarlier($to)) {
                $dateString = $from->toString(Zend_Date::ATOM) . '/' . $to->toString(Zend_Date::ATOM);
            }
        }

        if (!is_null($dateString)) {
            $shoppingProduct->setSalePriceEffectiveDate($dateString);
        }

        return $shoppingProduct;
    }
}
