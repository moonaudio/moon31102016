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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Columns
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected function _prepareForm()
    {
        $helper = Mage::helper('googlebasefeedgenerator');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $note = new Varien_Data_Form_Element_Note(array(
            'text' => '<ul class="messages"><li class="notice-msg"><ul><li>'
                . $helper->__('Feed output depends on your store configuration, types of products, available data stored in your products and others.')
                . '</li></ul></li></ul>'));
        $form->addElement($note);

        $fieldset = $form->addFieldset('columns', array('legend' => $helper->__('Columns Map')));
        $this->setFieldset($fieldset);

        $this->getFieldset()->addField('columns_fieldset_comment', 'hidden', array(
            'after_element_html' => $helper->__('<u>columns</u>:
                        <br />&#8226; <b>Order</b> - allows you to define the columns order in the feed. Higher numbers go last.
                        <br />&#8226; <b>Feed Column</b> - is the name of the column.
                        <br />&#8226; <b>Map To</b> - can be either <b>Directive</b> (first options in the dropdown), or a product <b>Attribute</b>. Directives have special logic, and Attributes simply grab the value from product.
                        <br />&#8226; <b>Options</b> - some Directives accept parameters that can be specified here.
                        <br /><br />')

        ));


        $this->getFieldset()->addType('map_product_columns_type', Mage::getConfig()->getBlockClassName('googlebasefeedgenerator/adminhtml_form_element_columns'));
        $this->addField('columns_map_product_columns', 'map_product_columns_type', array('name' => 'config[columns_map_product_columns]'));

        $form->setValues(Mage::registry('googlebasefeedgenerator_feed')->getConfig());
        return parent::_prepareForm();
    }
}