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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Filters
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected function _prepareForm()
    {
        $helper = Mage::helper('googlebasefeedgenerator');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $note = new Varien_Data_Form_Element_Note(array(
            'text' => '<ul class="messages"><li class="notice-msg"><ul><li>'
                . $helper->__('Filters here apply in the order as listed on the screen. Replace empty values rules use the order column for processing, and they can be set to create nested fill rules.')
                . '</li></ul></li></ul>'));
        $form->addElement($note);

        $fieldset = $form->addFieldset('filters', array('legend' => $helper->__('Product Filters')));
        $this->setFieldset($fieldset);

        $this->addField('filters_add_out_of_stock', 'select', array(
            'name'      => 'config[filters_add_out_of_stock]',
            'label'     => $helper->__('Allow Out of Stock'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note"></p>'
        ));

        $this->addField('filters_product_types', 'multiselect', array(
            'name'      => 'config[filters_product_types]',
            'label'     => $helper->__('Submit only products of these types'),
            'values'    => Mage::getModel('googlebasefeedgenerator/source_producttypes')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Products submitted to the feed have to be visible in Catalog, meaning visibility "Catalog", "Search", "Catalog, Search", but also "Not Visible Individually if they are part of a visible configurable".'). '</p>',
        ));

        $this->addField('filters_attribute_sets', 'multiselect', array(
            'name'      => 'config[filters_attribute_sets]',
            'label'     => $helper->__('Submit only products that have these attribute sets'),
            'values'    => Mage::getModel('googlebasefeedgenerator/source_attributesets')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('If you want to include All Attribute Sets, you can select only the first option; selecting the first option along with a few other sets won\'t include all sets.'). '</p><br />',
        ));

        $this->getFieldset()->addType('map_replace_empty_columns_type', Mage::getConfig()->getBlockClassName('googlebasefeedgenerator/adminhtml_form_element_emptycolumns'));
        $this->addField('filters_map_replace_empty_columns', 'map_replace_empty_columns_type', array(
            'name'      => 'config[filters_map_replace_empty_columns]',
            'label'     => $helper->__('Replace empty values'),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Columns must exist in <a href="#map_product_columns">Columns Map</a>. Save you config before looking for a new columns here. Grid has similar functions as <a href="#map_product_columns">Columns Map</a>'). '</p><br />',
        ));

        $this->getFieldset()->addType('find_and_replace_type', Mage::getConfig()->getBlockClassName('googlebasefeedgenerator/adminhtml_form_element_findandreplace'));
        $this->addField('filters_find_and_replace', 'find_and_replace_type', array(
            'name'      => 'config[filters_find_and_replace]',
            'label'     => $helper->__('Find And Replace'),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Save your colums before adding rules here. The find & replace will apply at column output. The more rules you apply the slower your feed will be, not recommended for large catalogs.'). '</p><br />',
        ));

        $this->addField('filters_skip_column_empty', 'multiselect', array(
            'name'      => 'config[filters_skip_column_empty]',
            'label'     => $helper->__('Skip Products with empty'),
            'values'    => Mage::getModel('googlebasefeedgenerator/source_feedcolumns')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Avoid having empty values for your items in the feed.<br />Columns must exist in <a href="#map_product_columns">Columns Map</a>, save your config before looking for columns here.'). '</p><br />',
        ));
        
        $this->addField('filters_skip_price_above', 'text', array(
            'name'      => 'config[filters_skip_price_above]',
            'label'     => $helper->__('Skip Products with Price above'),
            'class'     => 'validate-greater-than-zero validate-number',
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Products with price above value specified would be skipped. Filter does not apply when empty'),
        ));
        
        $this->addField('filters_skip_price_below', 'text', array(
            'name'      => 'config[filters_skip_price_below]',
            'label'     => $helper->__('Skip Products with Price below'),
            'class'     => 'validate-greater-than-zero validate-number',
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Products with price below value specified would be skipped. Filter does not apply when empty'),
        ));

        $this->getFieldset()->addType('adwords_price_buckets_type', Mage::getConfig()->getBlockClassName('googlebasefeedgenerator/adminhtml_form_element_mapprice'));
        $this->addField('filters_adwords_price_buckets', 'adwords_price_buckets_type', array(
            'name'      => 'config[filters_adwords_price_buckets]',
            'label'     => 'Adwords Price Buckets',
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('This gird is used to build a value in the column assigned to the "Adwords Price Buckets" directive, under <strong style="color:green;">Columns Map</strong>. Values with empty order are matched last.'). '</p><br />'
        ));

        $form->setValues(Mage::registry('googlebasefeedgenerator_feed')->getConfig());
        return parent::_prepareForm();
    }
}