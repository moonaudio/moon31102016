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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Schedule
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    protected $_schedules       = array();
    protected $_startAt         = false;
    protected $_batchMode       = false;
    protected $_defaultValues   = array();

    /**
     * Constructor. Set template and feed
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('googlebasefeedgenerator/grid/schedule.phtml');
        $feed = Mage::registry('googlebasefeedgenerator_feed');
        if (!$feed || ($feed && !$feed->getId())) {
            if (!$feed) {
                $feed = Mage::getModel('googlebasefeedgenerator/feed')->load(0);
            }
        }
        $this->setFeed($feed);
        if ($feed->hasData('default_feed_config') && is_array($feed->getData('default_feed_config'))) {
            $defaultConfig = $feed->getData('default_feed_config');
            if (isset($defaultConfig['schedule']) && is_array($defaultConfig['schedule']) ){
                foreach($defaultConfig['schedule'] as $key => $value) {
                    $this->_defaultValues[$key] = $value;
                }
            }
        }
        $this->_defaultValues['start_at'] = Mage::getModel('googlebasefeedgenerator/feed_schedule')->getNextStartAt();
        if (!$this->getSchedules()) {
            $schedule = new Varien_Object($this->_defaultValues);
            $schedule->setId('new_' . rand('10000000000', '99999999999'));
            $this->_schedules = array($schedule);
        }
    }

    /**
     * Preparing layout, adding buttons
     *
     * @return RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Schedule
     */
    protected function _prepareLayout()
    {
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('eav')->__('Delete'),
                    'class' => 'delete delete-option'
                )));

        $this->setChild('add_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('eav')->__('Add Schedule'),
                    'class' => 'add',
                    'id'    => 'add_new_option_button'
                )));
        return parent::_prepareLayout();
    }

    /**
     * Retrieve HTML of delete button
     *
     * @return string
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    /**
     * Retrieve HTML of add button
     *
     * @return string
     */
    public function getAddNewButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    /**
     * Retrieve existing schedules
     *
     * @return array
     */
    public function getSchedules()
    {
        if (!$this->_schedules) {
            if ($this->getFeed()->getId()) {
                $this->_schedules = Mage::getResourceModel('googlebasefeedgenerator/feed_schedule_collection')
                    ->addFieldToFilter('feed_id', $this->getFeed()->getId())
                    ->load();
            } else {
                $this->_schedules = array();
            }
        }
        return $this->_schedules;
    }

    /**
     * Returns start_at HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @return string
     */
    public function getStartAtHtml($id = false, $value = false)
    {
        if (!$this->_startAt) {
            $select = $this->getLayout()->createBlock('core/html_select');
            for($ii = 0; $ii < 24; $ii++) {
                $label = Mage::getSingleton('googlebasefeedgenerator/feed_schedule')->getTimeFormatted($ii);
                $select->addOption($ii, $label);
            }
            $this->_startAt = $select;
        } else {
            $select = $this->_startAt;
        }
        if ($id)  {
            $select->setId("schedule_start_at_$id");
            $select->setName("schedule[$id][start_at]");
        } else {
            $select->setId("schedule_start_at_{{id}}");
            $select->setName("schedule[{{id}}][start_at]");
        }
        if ($value === false) {
            $select->setValue($this->_getDefaultValue('start_at'));
        } else {
            $select->setValue($value);
        }
        return $select->toHtml();
    }

    /**
     * Returns batch_mode HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @return string
     */
    public function getBatchModeHtml($id = false, $value = false)
    {
        if (!$this->_batchMode) {
            $select = $this->getLayout()->createBlock('core/html_select');
            foreach (Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray() as $option) {
                $select->addOption($option['value'], $option['label']);
            }
            $select->setName('schedule[batch_mode][{{id}}]');
            $select->setId('schedule_batch_mode_{{id}}');
            $select->setClass('schedule-batch-mode');
            $select->setExtraParams('onChange="batchModeChange(this);"');
            $this->_batchMode = $select;
        } else {
            $select = $this->_batchMode;
        }
        if ($id)  {
            $select->setId("schedule_batch_mode_$id");
            $select->setName("schedule[$id][batch_mode]");
        } else {
            $select->setId("schedule_batch_mode_{{id}}");
            $select->setName("schedule[{{id}}][batch_mode]");
        }
        if ($value) {
            $select->setValue($value);
        } else {
            $select->setValue($this->_getDefaultValue('batch_mode'));
        }
        return $select->toHtml();
    }

    /**
     * Returns batch_mode HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @param string|boolean $batchMode
     * @return string
     */
    public function getBatchLimitHtml($id = false, $value = false, $batchMode = false)
    {
        $value = $value ? $value : $this->_getDefaultValue('batch_limit');
        $id = $id ? $id : '{{id}}';
        $additional = $batchMode ? "" : 'disabled="disabled" style="display:none"';
        return '<input name="schedule[' . $id . '][batch_limit]" value="' . $value . '" type="text" ' . $additional .
            ' id="schedule_batch_limit_' . $id . '" class="input-text"/>';
    }

    /**
     * Returns hidden delete HTML
     * 
     * @param string $id
     * @return string
     */
    public function getDeleteHiddenHtml($id = false)
    {
        $id = $id ? $id : '{{id}}';
        return '<input type="hidden" class="delete-flag" name="schedule[' . $id . '][delete]" value="" />';
    }

    /**
     * Returns field default value
     * 
     * @param string $key
     * @return string
     */
    protected function _getDefaultValue($key)
    {
        return array_key_exists($key, $this->_defaultValues) ? $this->_defaultValues[$key] : '';
    }
}
