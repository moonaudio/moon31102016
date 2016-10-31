<?php
class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Currency
{
    protected $_options;

    public function getStoreOptionsArray()
    {
        $stores = Mage::app()->getStores();
        $allCurrencies = Mage::app()->getLocale()->getOptionCurrencies();

        $options = array();

        foreach ($stores as $storeId => $storeName) {
            $store = Mage::app()->getStore($storeId);
            $allowedCurrencyCodes = $store->getAvailableCurrencyCodes();

            $options[$storeId] = array();
            foreach ($allCurrencies as $index => $currency) {
                if (in_array($currency['value'], $allowedCurrencyCodes)) {
                    $currency['default'] = false;
                    if ($store->getDefaultCurrencyCode() == $currency['value']) {
                        $currency['default'] = true;
                    }
                    $options[$storeId][$index] = $currency;
                }
            }
        }
        return $options;
    }

    public function toOptionArray()
    {
        if (!$this->_options) {
            /** @var RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed */
            $feed = Mage::registry('googlebasefeedgenerator_feed');
            $allCurrencies = Mage::app()->getLocale()->getOptionCurrencies();
            $allowedCurrencyCodes = $feed->getStore()->getAvailableCurrencyCodes();

            foreach ($allCurrencies as $index => $currency) {
                if (!in_array($currency['value'], $allowedCurrencyCodes)) {
                    unset($allCurrencies[$index]);
                }
            }
            // Reset the inner array counter
            $allCurrencies = array_values($allCurrencies);
            $this->_options = $allCurrencies;
        }
        $options = $this->_options;
        return $options;
    }
}