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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Grouped
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected function _prepareForm()
    {
        $helper= Mage::helper('googlebasefeedgenerator');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $note = new Varien_Data_Form_Element_Note(array(
            'text' => '<ul class="messages"><li class="notice-msg"><ul><li>'
                . $helper->__('This section applyes to all grouped and their associated produts in your catalog. Grouped type should be enabled under <strong style="color:green;">Filters</strong> section.')
                . '</li></ul></li></ul>'));
        $form->addElement($note);

        $fieldset = $form->addFieldset('grouped_products', array('legend' => $helper->__('Grouped products')));
        $this->setFieldset($fieldset);

        $this->addField('grouped_associated_products_mode', 'select', array(
            'name'      => 'config[grouped_associated_products_mode]',
            'label'     => $helper->__('How to add associated products'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_grouped_associated_mode')->toArray(),
        ));

        $this->addField('grouped_add_out_of_stock', 'select', array(
            'name'      => 'config[grouped_add_out_of_stock]',
            'label'     => $helper->__('Allow Out of Stock'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
        ));

        $this->addField('grouped_associated_products_link_add_unique', 'select', array(
            'name'      => 'config[grouped_associated_products_link_add_unique]',
            'label'     => $helper->__('Unique urls for associated products not visible'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('The new unique url will be formed from grouped product url and the ids of the associated product. I.e http://example.com/grouped.html?prod_id=123'). '</p>',
        ));

        $this->addField('grouped_price_display_mode', 'select', array(
            'name'      => 'config[grouped_price_display_mode]',
            'label'     => $helper->__('Price Type'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_grouped_price')->toArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('"Minimal price" is the lowest associated product price.<br />"Sum of associated products prices" is the default quantity of each associated product multiplied with the price of the associated product and than summed together.'). '</p><br />',
        ));

        $this->addField('grouped_associated_products_title', 'select', array(
            'name'      => 'config[grouped_associated_products_title]',
            'label'     => $helper->__('Fetch title from'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_associated')->toArray(),
        ));

        $this->addField('grouped_associated_products_description', 'select', array(
            'name'      => 'config[grouped_associated_products_description]',
            'label'     => $helper->__('Fetch description from'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_associated')->toArray(),
        ));
        $this->addField('grouped_associated_products_link', 'select', array(
            'name'      => 'config[grouped_associated_products_link]',
            'label'     => $helper->__('Fetch URL from'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_grouped_associated_link')->toArray(),
        ));

        $this->addField('grouped_associated_products_image_link', 'select', array(
            'name'      => 'config[grouped_associated_products_image_link]',
            'label'     => $helper->__('Fetch images from'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_associated')->toArray(),
            'after_element_html' => '<br />'
        ));

        $form->setValues(Mage::registry('googlebasefeedgenerator_feed')->getConfig());
        return parent::_prepareForm();
    }
}