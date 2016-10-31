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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Bundle
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected function _prepareForm()
    {
        $helper = Mage::helper('googlebasefeedgenerator');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $note = new Varien_Data_Form_Element_Note(array(
            'text' => '<ul class="messages"><li class="notice-msg"><ul><li>'
                . $helper->__('This section applyes to all bundle produts in your catalog. Bundle type should be enabled under <strong style="color:green;">Filters</strong> section.')
                . '</li></ul></li></ul>'));
        $form->addElement($note);

        $fieldset = $form->addFieldset('bundle_products', array('legend' => $helper->__('Bundle products')));
        $this->setFieldset($fieldset);

        $this->addField('bundle_associated_products_mode', 'select', array(
            'name'      => 'config[bundle_associated_products_mode]',
            'label'     => $helper->__('How to add option products'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_bundle_associated_mode')->toArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Bundle products are usually added as one item in the feed. Bundle sub-items could also be added.'). '</p>',
        ));

        $this->addField('bundle_combined_weight', 'select', array(
            'name'      => 'config[bundle_combined_weight]',
            'label'     => $helper->__('Combined weight'),
            'required'  => true,
            'values'    => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Bundle items can be defined as Dynamic or Fixed weight. This feature overwrites the bundle defintition and goes for Dynamic weight by computing weight as sum of all sub-items.'). '</p>',
        ));

        $form->setValues(Mage::registry('googlebasefeedgenerator_feed')->getConfig());
        return parent::_prepareForm();
    }
}