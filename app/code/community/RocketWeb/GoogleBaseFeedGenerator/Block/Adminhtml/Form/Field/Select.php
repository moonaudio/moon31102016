<?php


class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Form_Field_Select extends Varien_Data_Form_Element_Select
{
    public function getAfterElementHtml()
    {
        $currencies = Mage::getSingleton('googlebasefeedgenerator/source_currency')->getStoreOptionsArray();
        $html = '
        <script type="text/javascript">
            var storeCurrencies = \'' . Mage::helper('core')->jsonEncode($currencies) . '\';
            Event.observe(window, \'load\', function() {
                var feedEdit = new FeedEdit();
                feedEdit.setStoreList(storeCurrencies);

                Event.observe(\'store_id\', \'focus\', function() {
                    feedEdit.saveStoreChange(this);
                    $(this).stopObserving(\'focus\');
                });

                Event.observe(\'store_id\', \'change\', function() {
                    feedEdit.setStoreChange(this);
                });
            });
        </script>
        ';
        return $html . $this->getData('after_element_html');
    }
}