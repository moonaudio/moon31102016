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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Configurable
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected function _prepareForm()
    {
        $helper = Mage::helper('googlebasefeedgenerator');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $note = new Varien_Data_Form_Element_Note(array(
            'text' => '<ul class="messages"><li class="notice-msg"><ul><li>'
                . $helper->__('This section applyes to all configurable and their associated produts in your catalog. Configurable type should be enabled under <strong style="color:green;">Filters</strong> section.')
                . '</li></ul></li></ul>'));
        $form->addElement($note);

        $fieldset = $form->addFieldset('configurable_products', array('legend' => $helper->__('Configurable products')));
        $this->setFieldset($fieldset);

        $this->addField('configurable_associated_products_mode', 'select', array(
            'name'      => 'config[configurable_associated_products_mode]',
            'label'     => $helper->__('How to add associated products'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_associated_mode')->toArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Associated products can be added in the feed as separate items even if they are not visible in catalog.'). '</p>',
        ));

        $this->addField('configurable_add_out_of_stock', 'select', array(
            'name'      => 'config[configurable_add_out_of_stock]',
            'label'     => $helper->__('Allow Out of Stock'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('For associated products of configurable products.'). '</p>',
        ));

        $this->addField('configurable_inherit_parent_out_of_stock', 'select', array(
            'name'      => 'config[configurable_inherit_parent_out_of_stock]',
            'label'     => $helper->__('Inherit parent Out of Stock status'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Forces "Out of Stock" for all sub-items when the configurable item is Out of Stock.'). '</p>',
        ));

        $this->addField('configurable_associated_products_link_add_unique', 'select', array(
            'name'      => 'config[configurable_associated_products_link_add_unique]',
            'label'     => $helper->__('Unique urls for associated products not visible'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">The new unique url will be formed from configurable product url and the option ids as parameters.<br />e.g http://example.com/configurable.html?option_1=x&option2=y</p>'
        ));

        $this->addField('configurable_attribute_merge_value_separator', 'text', array(
            'name'      => 'config[configurable_attribute_merge_value_separator]',
            'label'     => $helper->__('Associated Product Attribute Value Separator'),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Variant attributes values like color and size, defined above, are been merged together for each item using the separator defined here.'). '</p><br />',
        ));

        $this->addField('configurable_associated_products_title', 'select', array(
            'name'      => 'config[configurable_associated_products_title]',
            'label'     => $helper->__('Fetch title from'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_associated')->toArray(),
        ));

        $this->addField('configurable_associated_products_description', 'select', array(
            'name'      => 'config[configurable_associated_products_description]',
            'label'     => $helper->__('Fetch description from'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_associated')->toArray(),
        ));

        $this->addField('configurable_associated_products_link', 'select', array(
            'name'      => 'config[configurable_associated_products_link]',
            'label'     => $helper->__('Fetch URL from'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_associated_link')->toArray(),
        ));

        $this->addField('configurable_associated_products_image_link', 'select', array(
            'name'      => 'config[configurable_associated_products_image_link]',
            'label'     => $helper->__('Fetch images from'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_associated')->toArray(),
            'after_element_html' => '<br />'
        ));

        $form->setValues(Mage::registry('googlebasefeedgenerator_feed')->getConfig());
        return parent::_prepareForm();
    }
}