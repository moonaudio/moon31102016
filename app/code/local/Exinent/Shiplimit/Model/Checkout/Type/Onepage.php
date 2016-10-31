<?php

class Exinent_Shiplimit_Model_Checkout_Type_Onepage extends Mage_Checkout_Model_Type_Onepage {

    public function saveShipping($data, $customerAddressId) {
        $result = parent::saveShipping($data, $customerAddressId);
        if (isset($result['error'])) {
            return $result;
        }
        $result = $this->validateCountryRestrict($data);
        if (isset($result['custom_error'])) {
            return $result;
        }
        return array();
    }

    public function saveBilling($data, $customerAddressId) {
        $result = parent::saveBilling($data, $customerAddressId);
        if (isset($result['error']))
            return $result;
        $usingCase = isset($data['use_for_shipping']) ? (int) $data['use_for_shipping'] : 0;
        if ($usingCase) {
            $result = $this->validateCountryRestrict($data);
            if (isset($result['custom_error']))
                return $result;
        }
        return array();
    }

    public function validateCountryRestrict($data) {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $show = array();
        foreach ($cart->getAllItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
            $res_array = explode(',', $product->getData('custom_countries'));
            if (!empty($res_array) && $res_array[0] != '') {
                if (!in_array($data['country_id'], $res_array)) {
                    $show[] = $item->getProduct()->getName();
                }
            }
        }
        if (count($show) >= 1) {
            $countryModel = Mage::getModel('directory/country')->loadByCode($data['country_id']);
            $countryName = $countryModel->getName();
            $products = implode(",", $show);
            $result = array('custom_error' => 1,
                'message' => Mage::helper('checkout')->__('Thanks for your order, but unfortunately due to the dealer restriction we cant ship the products %s to country %s, please remove these products to place the order', Mage::helper('core')->htmlEscape($products), Mage::helper('core')->htmlEscape($countryName)
            ));
            Mage::getSingleton('checkout/session')->addError($result['message']);
            session_write_close();
            $result['redirect'] = Mage::getUrl('checkout/cart');
            return $result;
        }
    }

    public function quoteValidateCountry($data) {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $show = array();
        foreach ($cart->getAllItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
            $res_array = explode(',', $product->getData('custom_countries'));
            if (!empty($res_array) && $res_array[0] != '') {
                if (!in_array($data, $res_array)) {
                    $show[] = $item->getProduct()->getName();
                }
            }
        }
        if (count($show) >= 1) {
            $countryModel = Mage::getModel('directory/country')->loadByCode($data);
            $countryName = $countryModel->getName();
            $responce['products'] = $show;
            $responce['contry'] = $countryName;
            return $responce;
        }
    }

}

?>