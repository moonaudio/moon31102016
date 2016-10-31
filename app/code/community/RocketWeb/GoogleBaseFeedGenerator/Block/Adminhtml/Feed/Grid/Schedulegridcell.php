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
class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Grid_Schedulegridcell extends Mage_Adminhtml_Block_Template
{
    protected $_schedules = array();

    /**
     * Internal constructor, that is called from real constructor. Sets template
     */
    protected function _construct()
    {
        $this->setTemplate('googlebasefeedgenerator/grid/schedulegridcell.phtml');
        parent::_construct();
    }

    /**
     * Returns edit schedule URL
     * 
     * @return string
     */
    public function getEditUrl()
    {
        return $this->getUrl('*/*/edit', array('id' => $this->getFeed()->getId(),
            '_query' => array('active_tab' => 'googlebasefeedgenerator_schedule')));
    }

    /**
     * Retrieve existing schedules
     *
     * @return array
     */
    public function getSchedules()
    {
        $feedId = $this->getFeed()->getId();
        if (!array_key_exists($feedId, $this->_schedules)) {
            if ($this->getFeed()->getId()) {
                $this->_schedules[$feedId] = Mage::getResourceModel('googlebasefeedgenerator/feed_schedule_collection')
                    ->addFieldToFilter('feed_id', $this->getFeed()->getId())
                    ->load();
            } else {
                $this->_schedules[$feedId] = array();
            }
        }
        return $this->_schedules[$feedId];
    }
}
