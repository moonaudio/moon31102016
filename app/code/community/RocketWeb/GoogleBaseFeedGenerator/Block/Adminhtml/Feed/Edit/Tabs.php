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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    static public $tabs = array(
        'general'       => array('title' => 'General Configuration'),
        'columns'       => array('title' => 'Columns Map'),
        'categories'    => array('title' => 'Categories Map'),
        'filters'       => array('title' => 'Product Filters'),
        'options'       => array('title' => 'Product Options'),
        'configurable'  => array('title' => 'Configurable products'),
        'grouped'       => array('title' => 'Grouped products'),
        'bundle'        => array('title' => 'Bundle products'),
        'shipping'      => array('title' => 'Shipping'),
        'schedule'      => array('title' => 'Run Schedule'),
        'ftp'           => array('title' => 'FTP Uploads'),
    );

    public function _construct()
    {
        parent::_construct();
        $this->setId('form_tabs');
        $this->setDestElementId('edit_form'); // this should be same as the form id define above
        $this->setTitle(Mage::helper('googlebasefeedgenerator')->__('Feed Information'));
        $this->setTemplate('googlebasefeedgenerator/tabs.phtml');
    }

    protected function _beforeToHtml()
    {
        $feed = Mage::registry('googlebasefeedgenerator_feed');
        foreach (array_keys($feed->getData('default_feed_config')) as $key) {
            $this->addTab('googlebasefeedgenerator_'. $key, array(
                'label'     => Mage::helper('googlebasefeedgenerator')->__(self::$tabs[$key]['title']),
                'title'     => Mage::helper('googlebasefeedgenerator')->__(self::$tabs[$key]['title']),
                'content'   => $this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_feed_edit_tab_'. $key)->toHtml(),
            ));
        }

        return parent::_beforeToHtml();
    }
}
