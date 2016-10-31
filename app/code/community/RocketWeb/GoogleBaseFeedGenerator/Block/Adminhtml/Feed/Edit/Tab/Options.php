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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Options
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected function _prepareForm()
    {
        $helper= Mage::helper('googlebasefeedgenerator');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $note = new Varien_Data_Form_Element_Note(array(
            'text' => '<ul class="messages"><li class="notice-msg"><ul><li>'
                . $helper->__('In order for the feed to varry products by their options, the <strong style="color:green;">Feed Columns</strong> needs to have the variant columns (i.e. size and color) mapped to the <strong style="color:green;">Product Options</strong> directive.')
                . '</li><li>'. $helper->__('Use the <strong style="color:green;">Product Option</strong> directive parameter to specify which options should be pulled.')
                . '</li></ul></li></ul>'));
        $form->addElement($note);

        $fieldset = $form->addFieldset('product_options', array('legend' => $helper->__('Product Options')));
        $this->setFieldset($fieldset);

        $this->addField('options_mode', 'select', array(
            'name'      => 'config[options_mode]',
            'label'     => $helper->__('How to add product options'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/source_product_option')->toOptionArray(),
            'after_element_html' => '<p class="note" style="width:450px;">'. $helper->__('Detail product options into one single row or multiple rows, one for each option.'). '</p><br />'
        ));

        $this->getFieldset()->addType('options_vary_categories_type', Mage::getConfig()->getBlockClassName('googlebasefeedgenerator/adminhtml_form_element_category_option'));
        $this->addField('options_vary_categories', 'options_vary_categories_type', array(
            'name'      => 'config[options_vary_categories]',
            'label'     => $helper->__('Multiple rows only for products in these categories'),
            'required'  => true,
        ));

        $form->setValues(Mage::registry('googlebasefeedgenerator_feed')->getConfig());
        return parent::_prepareForm();
    }
}