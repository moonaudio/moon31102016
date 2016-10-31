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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
    * Init class
    */
    public function __construct()
    {
        parent::__construct();
        $this->setId('googlebasefeedgenerator_feed_form');
        $this->setTitle(Mage::helper('googlebasefeedgenerator')->__('Feed Configuration'));
    }

    /**
    * Setup form fields for inserts/updates
    *
    * return Mage_Adminhtml_Block_Widget_Form
    */
    protected function _prepareForm()
    {
        $model = Mage::registry('googlebasefeedgenerator_feed');

        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method'    => 'post'
        ));

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}