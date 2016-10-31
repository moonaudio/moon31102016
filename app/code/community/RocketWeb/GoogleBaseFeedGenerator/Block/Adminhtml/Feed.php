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
 * @copyright  Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Modify header & button labels
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_feed';
        $this->_blockGroup = 'googlebasefeedgenerator';
        $this->_headerText = Mage::helper('googlebasefeedgenerator')->__('Manage Rocket Feeds');
        parent::__construct();

        $this->_removeButton('add');

//        $this->_addButton('run', array(
//            'label'     => Mage::helper('googlebasefeedgenerator')->__('Run All Feeds'),
//            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/generateall') .'\')',
//            'class'     => 'save',
//        ));
        $this->_addButton('add', array(
            'label'     => Mage::helper('googlebasefeedgenerator')->__('Add New Feed'),
            'onclick'   => 'setLocation(\'' . $this->getAddFeedUrl() .'\')',
            'class'     => 'add',
        ));
    }

    /**
     * If no feed types active, just go with the generic type, and skip the type selection,
     * in which case we go directly to edit as it defaults to generic type
     *
     * @return string
     */
    private function getAddFeedUrl()
    {
        if (count(Mage::getModel('googlebasefeedgenerator/feed_type')->getOptionArray())) {
            return $this->getUrl('*/*/new');
        }
        return $this->getUrl('*/*/edit');
    }
    /**
     * Redefine header css class
     *
     * @return string
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-customer-groups';
    }
}