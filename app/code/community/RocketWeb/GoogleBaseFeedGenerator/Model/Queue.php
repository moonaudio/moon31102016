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
class RocketWeb_GoogleBaseFeedGenerator_Model_Queue extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('googlebasefeedgenerator/queue');
    }

    /**
     * Add queue messages messages
     *
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed
     * @param string $message
     * @param boolean | RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Schedule $schedule
     * @return $this
     */
    public function send(RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed, $message = '', $schedule = false)
    {
        $this->load($feed->getId(), 'feed_id');

        // Get correct magento hour
        $time = new Zend_Date(Mage::getModel('core/date')->timestamp(time()), Zend_Date::TIMESTAMP);

        // Schedule is only set when cron processes the Observer::processSchedule(),
        // the rest of the times items are added in the queue manually and do not have a schedule. First schedule is been used.
        if (!$schedule || ($schedule && !$schedule->getId())) {
            $schedule = $feed->getSchedule();
        }
        if ($schedule->getBatchMode()) {
            $message .= ' | batch ';
        }

        $this->addData(array(
            'feed_id'       => $feed->getId(),
            'message'       => $message,
            'is_read'       => 0,
            'created_at'    => $time->get(Zend_Date::ISO_8601),
            'schedule_id'   => $schedule->getId(),
        ));
        $this->save();

        return $this;
    }

    /**
     * Read new message
     *
     * @return Mage_Core_Model_Abstract
     */
    public function read()
    {
        // Get correct magento hour
        $time = new Zend_Date(Mage::getModel('core/date')->timestamp(time()), Zend_Date::TIMESTAMP);

        /** @var RocketWeb_GoogleBaseFeedGenerator_Model_Mysql4_Feed_Collection $collection */
        $collection = $this->getResourceCollection()
            ->addFieldToSelect('id')
            ->setOrder('is_read', Varien_Data_Collection_Db::SORT_ORDER_ASC)
            ->setOrder('created_at', Varien_Data_Collection_Db::SORT_ORDER_DESC);
        $select = $collection->getSelect()
            ->where('is_read = 0')
            ->orWhere('is_read = 1 && TO_DAYS(`created_at`) < TO_DAYS(\''. $time->get(Zend_Date::ISO_8601). '\')')
            ->limit(1);

        $id = $collection->getConnection()->fetchOne($select);
        return $this->load($id);
    }

    /**
     * Acknowledge the read.
     *
     * @param null $id
     * @return $this
     */
    public function lock($id = null)
    {
        if (!is_null($id)) {
            $this->load($id);
        }
        $this->setData('is_read', 1)->save();
        $this->getResource()->commit();

        return $this;
    }

    /**
     * Unset the acknolegdment, usually for batch mode so that the feed can run again
     *
     * @param null $id
     * @return $this
     */
    public function unlock($id = null)
    {
        if (!is_null($id)) {
            $this->load($id);
        }
        $this->setData('is_read', 0)->save();
        $this->getResource()->commit();

        return $this;
    }

    /**
     * Delete message from queue after processing
     *
     * @param null $id
     * @return Mage_Core_Model_Abstract
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function delete($id = null)
    {
        if (!is_null($id)) {
            $this->load($id);
        }
        if (!$this->getid() || !$this->getData('is_read')) {
            throw new Mage_Core_Exception('Cannot delete a feed that has not been marked is_read.');
        }
        return parent::delete();
    }
}