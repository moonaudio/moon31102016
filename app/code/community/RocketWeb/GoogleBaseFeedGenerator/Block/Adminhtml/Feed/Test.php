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
 * @category  RocketWeb
 * @package   RocketWeb_GoogleBaseFeedGenerator
 * @copyright Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */

/**
 * Custom renderer
 */
class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Test extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Init class
     */
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'googlebasefeedgenerator';
        $this->_controller = 'adminhtml_feed';
        $this->_mode = 'test';

        $this->_removeButton('back')
             ->_removeButton('reset')
             ->_removeButton('delete')
             ->_removeButton('save');

        $this->_addButton('back', array(
            'label'     => Mage::helper('adminhtml')->__('Close Window'),
            'onclick'   => 'window.close();',
            'class'     => 'back',
        ), -1);

        $this->_addButton('test', array(
            'label'     => Mage::helper('adminhtml')->__('Test Feed Now'),
            'id'        => 'btn_test_feed',
            'onclick'   => "editForm.submit();",
            'class'     => 'save',
        ), -1, 0, 'footer');
    }

    /**
     * Get Header text
     * @return string
     */
    public function getHeaderText()
    {
        return sprintf(Mage::helper('googlebasefeedgenerator')->__('Test "%s" feed'), Mage::registry('googlebasefeedgenerator_feed')->getName());
    }

    public function getHeaderCssClass()
    {
        return 'icon-head head-customer-groups';
    }
}
