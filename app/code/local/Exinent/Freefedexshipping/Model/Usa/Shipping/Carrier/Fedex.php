<?php

class Exinent_Freefedexshipping_Model_Usa_Shipping_Carrier_Fedex extends Mage_Usa_Model_Shipping_Carrier_Fedex {

    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
        $originalResult = parent::collectRates($request);
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $product = Mage::getModel('catalog/product');
        $flag = false;
        foreach ($cart->getAllItems() as $item) {
            if ($product->load($item->getProduct()->getId())->getAttributeText('special_shipping_group') == "Free 2nd Day Shipping") {
                $flag = true;
                break;
            }
        }
        if ($flag) {
            foreach ($originalResult->getAllRates() as $method) {
                if ($method->getmethod() == "FEDEX_2_DAY") {
                    $method->setPrice(0);
                }
            }
        }
        return $this->getResult();
    }

}
