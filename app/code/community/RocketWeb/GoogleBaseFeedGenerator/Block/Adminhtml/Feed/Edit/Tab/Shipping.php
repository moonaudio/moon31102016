<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Shipping
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected function _prepareLayout()
    {

    }
    protected function _prepareForm()
    {
        $helper = Mage::helper('googlebasefeedgenerator');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $note = new Varien_Data_Form_Element_Note(array(
            'text' => '<ul class="messages"><li class="notice-msg"><ul><li>'
                . $helper->__('This setting tunes how <strong style="color:green;">Shipping</strong> directive works.') . ' '
                . $helper->__('You must also add your shipping column under <strong style="color:green;">Columns Map</strong> and map it to the <strong style="color:green;">Shipping</strong> directive.')
                . '</li></ul></li></ul>'));
        $form->addElement($note);


        $fieldset = $form->addFieldset('shipping', array('legend' => $helper->__('Shipping')));
        $this->setFieldset($fieldset);

        $this->addField('shipping_methods', 'multiselect', array(
            'name'      => 'config[shipping_methods]',
            'label'     => $helper->__('Methods'),
            'values'    => Mage::getModel('googlebasefeedgenerator/source_shipping_allmethods')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Allowed shipping methods. Realtime carriers aren\'t allowed to avoid getting banned or to spam carriers\' servers. e.g. UPS, USPS, FedEx, DHL, Royal Mail, ..<br />Please add/configure any realtime carriers in your Google Merchant account.'). '</p>',
        ));

        $this->addField('shipping_country', 'multiselect', array(
            'name'      => 'config[shipping_country]',
            'label'     => $helper->__('Countries'),
            'values'    => Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Shipping allowed countries. Select only a few countries the avoid a very long feed generation and to keep the feed size to a minimnum.'). '</p>',
        ));

        $this->addField('shipping_only_minimum', 'select', array(
            'name'      => 'config[shipping_only_minimum]',
            'label'     => $helper->__('Only Minimum Price'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('If there are more carriers/shipping methods than the shipping column will be filled with only the minimum price and the related carrier/shipping method.'). '</p>',
        ));

        $this->addField('shipping_only_free_shipping', 'select', array(
            'name'      => 'config[shipping_only_free_shipping]',
            'label'     => $helper->__('Only Free Shipping'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Add only free shipping when is available.'). '</p>',
        ));

        $this->addField('shipping_add_tax_to_price', 'select', array(
            'name'      => 'config[shipping_add_tax_to_price]',
            'label'     => $helper->__('Add Tax to Shipping Price'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('For US feeds column \'price\' should not include tax.'). '</p><br />',
        ));

        $form->setValues(Mage::registry('googlebasefeedgenerator_feed')->getConfig());
        return parent::_prepareForm();
    }
}