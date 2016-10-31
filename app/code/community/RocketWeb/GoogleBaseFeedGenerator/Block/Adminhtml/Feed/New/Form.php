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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_New_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Setup form fields
     *
     * return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $helper = Mage::helper('googlebasefeedgenerator');

        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getUrl('*/*/edit'),
            'method'    => 'post'
        ));

        $fieldset = $form->addFieldset('input', array(
            'legend'    => $helper->__('What kind of feed ?'),
        ));

        $fieldset->addField('name', 'text', array(
            'name'      => 'name',
            'label'     => $helper->__('Feed Name'),
            'required'  => true,
        ));

        $fieldset->addField('type', 'select', array(
            'name'      => 'type',
            'label'     => $helper->__('Feed Type'),
            'required'  => true,
            'values'    => Mage::getModel('googlebasefeedgenerator/feed_type')->getOptionArray(),
        ));

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}