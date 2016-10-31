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
 * @copyright Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Batch extends Varien_Object
{
    protected $started_at = 0;
    protected $ended_at = 0;

    public function _construct()
    {
        parent::_construct();
        $this->started_at = Mage::getModel('core/date')->timestamp(time());

        $this->setData('cdate', $this->started_at);
        if (!$this->hasData('offset')) {
            $this->setData('offset', 0);
        }
        if (!$this->hasData('limit')) {
            $this->setData('limit', $this->getGenerator()->getFeed()->getSchedule()->getBatchLimit());
        }
        if (!$this->hasData('total_items')) {
            $this->setData('total_items', $this->getGenerator()->getTotalItems());
        }
    }

    /**
     * @return bool
     */
    public function aquireLock()
    {
        if (!file_exists($this->getLockPath())) {
            $f = @fopen($this->getLockPath(), "w");
            @fclose($f);
            if (!file_exists($this->getLockPath())) {
                Mage::throwException(sprintf('Can\'t create lock file %s', $this->getLockPath()));
            }
        }

        if (($this->_locked = $this->isLocked()) == true) {
            $this->log(sprintf('Can\'t aquire batch lock for script [%s]', $this->getScheduleId()));
            return false;
        }

        $this->lock();
        return true;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        $locked = false;
        $lock_data = $this->readFile();
        // Locked if another script is running.
        if ($lock_data['id'] != $this->getScheduleId() && $lock_data['status'] == Mage_Cron_Model_Schedule::STATUS_RUNNING) {
            $this->log(sprintf('Script [%s] is already running', $lock_data['id']));
            // Aquire lock if expired (expires in almost 24 hours)
            if (!$this->isLockExpired($lock_data['queue_started_at'])) {
                $locked = true;
            } else {
                $this->log(
                    sprintf(
                        'Lock of script [%s] has expired at %s, script [%s] will aquire lock (cdate is [%s])',
                        $lock_data['id'],
                        date('Y-m-d H:i:s', date('Y-m-d 23:59:59', $this->getCdate())),
                        $this->getScheduleId(),
                        date('Y-m-d H:i:s', $this->started_at)
                    )
                );
            }
        }

        // Fail safe - don't allow to run too many times even if cron missed or unknown error was triggered in the past.
        if ($lock_data['offset'] > 0 && $lock_data['fail_safe'] >= ceil($this->getTotalItems() / $this->getLimit()) + max(ceil(ceil($this->getTotalItems() / $this->getLimit()) / 2), 2)) {
            $locked = true;
            if (!$this->isLockExpired($lock_data['queue_started_at'])) {
                $locked = false;
                $this->log(sprintf('Script was executed too many times %d', $lock_data['fail_safe']));
            }
        }

        // Allow only 1 complete feed generation in a single day.
        if ($this->completedForToday()) {
            $locked = true;
        }

        return $locked;
    }

    /**
     * @return bool
     */
    public function completedForToday()
    {
        if (file_exists($this->getLockPath())) {
            $lock_data = $this->readFile();
            return $this->getQueueFinished($lock_data['offset'], $lock_data['total']) && !$this->isLockExpired($lock_data['queue_started_at']);
        }
        return false;
    }

    /**
     * Test if this batch is the last one to complete a feed generation and if it is completed.
     * 
     * @return bool
     */
    public function completed()
    {
        if (file_exists($this->getLockPath())) {
            $lock_data = $this->readFile();
            return $this->getQueueFinished($lock_data['offset'], $lock_data['total']);
        }
        return false;
    }

    /**
     * @param $time
     * @return bool
     */
    public function isLockExpired($time)
    {
        $expired = true;
        $cdate = $this->getCdate();
        $cday_s = mktime(0, 0, 0, date('m', $cdate), date('d', $cdate), date('Y', $cdate));
        $cday_e = mktime(23, 59, 59, date('m', $cdate), date('d', $cdate), date('Y', $cdate));

        if ($time >= $cday_s && $time <= $cday_e) {
            $expired = false;
        }

        return $expired;
    }

    /**
     * @return $this
     */
    public function lock()
    {
        $lock_data = $this->readFile();

        $this->setIsNew(false);
        if ($lock_data['offset'] == 0) {
            $this->setIsNew(true);
        }

        if ($this->getQueueFinished($lock_data['offset'], $this->getTotalItems())) {
            $lock_data = $this->resetLockData();
            $this->setIsNew(true);
        }

        $lock_data['id'] = $this->getScheduleId();
        // Declare as processed to prevent loops by incrementing offset from the begining.
        $this->setOffset($this->getNextOffset($lock_data['offset'], $this->getLimit()));
        $lock_data['offset'] = $this->getOffset();
        $lock_data['total'] = $this->getTotalItems();
        $lock_data['limit'] = $this->getLimit();
        $lock_data['started_at'] = $this->started_at;
        $lock_data['ended_at'] = 0;
        $lock_data['status'] = Mage_Cron_Model_Schedule::STATUS_RUNNING;
        $lock_data['fail_safe'] += 1;

        $this->writeFile($lock_data);
        $this->log(sprintf('Batch ID %d aquired lock. Offset %d, Limit %d', $this->getScheduleId(), $this->getOffset() - $this->getLimit(), $this->getLimit()));
        return $this;
    }

    /**
     * Updates lock on the current offset in case of early exit
     *
     * @param int $offset
     */
    public function updateLockOffset($offset)
    {
        $lockData = $this->readFile();
        $lockData['offset'] = $offset;
        $this->writeFile($lockData);
    }

    /**
     * @return bool
     */
    public function releaseLock()
    {
        $this->unlock();
        return true;
    }

    /**
     * @param $offset
     * @param $total
     * @return bool
     */
    public function getQueueFinished($offset, $total)
    {
        if ($offset >= $total) {
            return true;
        }
        return false;
    }

    /**
     * @return $this
     */
    protected function unlock()
    {
        $lock_data = $this->readFile();
        $lock_data['ended_at'] = Mage::getModel('core/date')->timestamp(time());
        $lock_data['items_added'] = $this->getGenerator()->getCountProductsExported();
        $lock_data['items_skipped'] = $this->getGenerator()->getCountProductsSkipped();
        $lock_data['status'] = Mage_Cron_Model_Schedule::STATUS_SUCCESS;
        $this->writeFile($lock_data);
        $this->log(sprintf('Batch ID %d released lock', $this->getScheduleId()));
        return $this;
    }

    /**
     * @param $offset
     * @param $limit
     * @return mixed
     */
    protected function getNextOffset($offset, $limit)
    {
        return $offset + $limit;
    }

    /**
     * @param $past_runs
     * @return float|mixed
     */
    public function getEstimateJobs($past_runs = 0)
    {
        $estimate = ceil($this->getTotalItems() / $this->getLimit()) - $past_runs;
        $estimate = min($estimate, ceil($this->getTotalItems() / $this->getLimit()) - $this->getOffset() / $this->getLimit());
        return $estimate;
    }

    /**
     * @param $mixed
     * @return mixed
     */
    public function writeFile($mixed)
    {
        $time = new Zend_Date(Mage::getModel('core/date')->timestamp(time()), Zend_Date::TIMESTAMP);
        $mixed['updated_at'] = $time->get(Zend_Date::TIMESTAMP);
        if (!is_writable($this->getLockPath())) {
            Mage::throwException(sprintf('Can\t write to %s', $this->getLockPath()));
        }
        if (file_put_contents($this->getLockPath(), @serialize($mixed), LOCK_EX) === false) {
            Mage::throwException(sprintf('Error writing to %s', $this->getLockPath()));
        }
        @chmod(rtrim(Mage::getBaseDir(), DS) . DS . rtrim($this->getGenerator()->getFeed()->getConfig('general_feed_dir'), DS), 0755);
        @chmod($this->getLockPath(), 0664);
        return $mixed;
    }

    /**
     * @return array|mixed|string
     */
    public function readFile()
    {
        $mixed = file_get_contents($this->getLockPath());
        if ($mixed === false) {
            Mage::throwException(sprintf('Error reading from %s', $this->getLockPath()));
        }
        $mixed = @unserialize($mixed);
        if (empty($mixed)) {
            $mixed = $this->resetLockData();
            $mixed['created_at'] = Mage::getModel('core/date')->timestamp(time());
        }
        return $mixed;
    }

    /**
     * @return array
     */
    protected function resetLockData()
    {
        return array(
            'id' => false,
            'offset' => 0,
            'items_added' => 0,
            'items_skipped' => 0,
            'total' => $this->getTotalItems(),
            'limit' => $this->getLimit(),
            'status' => Mage_Cron_Model_Schedule::STATUS_PENDING,
            'started_at' => 0,
            'ended_at' => 0,
            'updated_at' => 0,
            'queue_started_at' => Mage::getModel('core/date')->timestamp(time()),
            'fail_safe' => 0);
    }

    /**
     * @return string
     */
    public function getLockPath()
    {
        return $this->getGenerator()->getBatchLockPath();
    }

    /**
     * @return mixed
     */
    public function getLocked()
    {
        return $this->_locked;
    }

    /**
     * @param $msg
     * @param null $level
     */
    public function log($msg, $level = null)
    {
        $this->getGenerator()->log($msg, $level);
    }
}