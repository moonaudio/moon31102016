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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_General
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected function _prepareForm()
    {
        $field = array();
        $helper = Mage::helper('googlebasefeedgenerator');
        $feed = Mage::registry('googlebasefeedgenerator_feed');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('feed', array('legend' => $this->__('Feed Settings')));
        $this->setFieldset($fieldset);

        $this->getFieldset()->addField('general_fieldset_comment', 'hidden', array(

        ));

        if ($feed->getId()) {
            $this->getFieldset()->addField('id', 'hidden', array(
                'name' => 'id',
                'value' => $feed->getId() ? $feed->getId() : 0
            ));
        }

        if ($feed->getType()) {
            $this->getFieldset()->addField('type', 'hidden', array(
                'name' => 'type',
                'value' => $feed->getType()
            ));
        }

        $this->getFieldset()->addField('name', 'text', array(
            'name'      => 'name',
            'label'     => $helper->__('Name'),
            'required'  => true,
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('The name of the Feed'). '</p>'
        ));

        $this->getFieldset()->addField('store_id', 'select', array(
            'name' => 'store_id',
            'label' => $helper->__('Store View'),
            'required' => true,
            'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, false),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Specify from which store the feed will pull data.'). '</p>'
        ));

        $fieldset->addType('advanced_select', Mage::getConfig()->getBlockClassName('googlebasefeedgenerator/adminhtml_form_field_select'));
        $fieldset->addField('general_currency', 'advanced_select', array(
            'name' => 'config[general_currency]',
            'label' => $helper->__('Feed Currency'),
            'required' => true,
            'values' => Mage::getSingleton('googlebasefeedgenerator/source_currency')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('This lists only allowed currencies on the store view.') . '<br>'
                . $helper->__('WARNING: Changing to a currency which is not displayed on frontend can lead to feed being rejected with provider!') . '</p>'
        ));

        $this->getFieldset()->addField('general_feed_dir', 'text', array(
            'name'      => 'config[general_feed_dir]',
            'label'     => $helper->__('Feed path'),
            'required' => true,
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('It\'s the dir path to save the feed. Assure write permissions.'). '</p>'
        ));

        $fieldset1 = $form->addFieldset('feed_extra', array(
            'legend'    => $this->__('General Configuration')
        ));
        $this->setFieldset($fieldset1);

        $this->addField('general_apply_catalog_price_rules', 'select', array(
            'name'      => 'config[general_apply_catalog_price_rules]',
            'label'     => $helper->__('Apply Catalog Price Rules'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('To exclude catalog promo price set this to No.'). '</p>'
        ));

        $field['use_default_stock'] = $this->addField('general_use_default_stock', 'select', array(
            'name'      => 'config[general_use_default_stock]',
            'label'     => 'Use default Stock Statuses',
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('If your store is using a custom attribute for stock status, change this to No.'). '</p>'
        ));

        $field['stock_attribute_code'] = $this->addField('general_stock_attribute_code', 'select', array(
            'name'      => 'config[general_stock_attribute_code]',
            'label'     => $helper->__('Alternate Stock/Availability Attribute.'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_productattributescodes')->toArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('To fill \'availability\'. The attribute\'s values can be: \'in stock\', \'available for order\', \'out of stock\', \'preorder\'. Other values will be replaced by \'out of stock\'.'). '</p>'
        ));

        $this->addField('general_use_image_cache', 'select', array(
            'name'      => 'config[general_use_image_cache]',
            'label'     => 'Use image cache',
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Enabeling image cache will cause output of Product Image URL and Product Additional Image URL directives to attempt create url to images using cache instead of direct media/image url.'). '</p>'
        ));

        $this->addField('general_max_title_length', 'text', array(
            'name'      => 'config[general_max_title_length]',
            'label'     => $helper->__('Max Length of Title.'),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Maximum length of title. Longer texts will be chunked. Recommended max length is 70. Set to empty, the title will not be chunked.'). '</p>'
        ));

        $this->addField('general_max_description_length', 'text', array(
            'name'      => 'config[general_max_description_length]',
            'label'     => $helper->__('Max Length of Description.'),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Maximum length of description. Longer texts will be chunked. Recommended max length is 500-1000, but no longer than 10000. Set to empty, the description will not be chunked.'). '</p>'
        ));

        $dependency = $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence');
        if ($field['stock_attribute_code'] && $field['use_default_stock']) {
            $dependency
                ->addFieldMap($field['stock_attribute_code']->getHtmlId(), $field['stock_attribute_code']->getName())
                ->addFieldMap($field['use_default_stock']->getHtmlId(), $field['use_default_stock']->getName())
                ->addFieldDependence(
                    $field['stock_attribute_code']->getName(),
                    $field['use_default_stock']->getName(),
                    '0');
        }
        $this->setChild('form_after', $dependency);

        $data = $feed->getConfig();
        $data['id'] = $feed->getId();
        $data['name'] = $feed->getName();
        $data['type'] = $feed->getType();
        $data['store_id'] = $feed->getStoreId();

        $form->setValues($data);
        return parent::_prepareForm();
    }
}