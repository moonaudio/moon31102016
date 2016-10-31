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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
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

        $this->_updateButton('save', 'label', $this->__('Save Feed'));
        $this->_updateButton('delete', 'label', $this->__('Delete'));
        $this->_removeButton('reset');

        $id = Mage::registry('googlebasefeedgenerator_feed')->getId();
        if ($id) {
            $this->_updateButton('save', 'label', $this->__('Save Config'));
            $this->_addButton('test', array(
                'label' => Mage::helper('adminhtml')->__('Test Feed'),
                'onclick' => 'popWin(\'' . $this->getUrl('*/*/test', array('id' => $id)) . '\',\'_blank\',\'width=800,height=700,resizable=1,scrollbars=1\');return false;',
            ), -1);
            $this->_addButton('export', array(
                'label' => Mage::helper('adminhtml')->__('Export Config'),
                'onclick' => 'setLocation(\'' . $this->getUrl('*/*/export', array('id' => $id)) . '\')',
            ), -1);
        }
        // maybe we should add buttons here for massactions: Enable, Disable, Clone
    }

    /**
     * Get Header text
     * @return string
     */
    public function getHeaderText()
    {
        $feed = Mage::registry('googlebasefeedgenerator_feed');
        if ($feed->getId()) {
            $str = Mage::helper('googlebasefeedgenerator')->__('Edit %s');
        }
        else {
            $str = Mage::helper('googlebasefeedgenerator')->__('New %s');
        }
        return sprintf($str, RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Type::getLabel($feed->getType()));
    }

    public function getHeaderCssClass()
    {
        return 'icon-head head-customer-groups';
    }
}
