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

class RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Schedule extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('googlebasefeedgenerator/feed_schedule');
    }

    /**
     * @return int
     */
    public function getBatchLimit()
    {
        $default = Mage::getConfig()->getNode('default/general/batch_limit');
        if((int)$this->getData('batch_limit') < 1) {
            return (int)$default;
        }
        return (int)$this->getData('batch_limit');
    }

    /**
     * Set defaults
     *
     * @return $this
     */
    public function reset()
    {
        $this->setData(array(
            'start_at' => 1,
            'batch_mode' => Mage::getConfig()->getNode('default/general/batch_mode')->asArray(),
            'batch_limit' => Mage::getConfig()->getNode('default/general/batch_limit')->asArray()
        ));
        return $this;
    }

    /**
     * Get the next available hour in the schedule
     *
     * @return int
     */
    public function getNextStartAt($alreadyUsed = array())
    {
        $used = array_merge($alreadyUsed, $this->getResource()->getAllStartAt());
        $max = count($used) ? max($used) : -1;
        $hour = false;
        if ($max < 23) {
            $counter = $max;
            do {
                if (!in_array(($counter+1), $used)) {
                    $hour = $counter+1;
                    break;
                }
                $counter++;
            } while ($counter < 23);
        }
        if (!$hour) {
            $counter = $max;
            while ($counter > 0) {
                if (!in_array(($counter-1), $used)) {
                    $hour = $counter-1;
                    break;
                }
                $counter--;
            }
        }
        $hour = $hour ? $hour : 0;
        return $hour;
    }

    /**
     * @return string
     */
    public function __toString() {

        $startAtFormatted = $this->getTimeFormatted($this->getStartAt());
        if ($this->getBatchMode() != 1) {
            return 'Daily at ' . $startAtFormatted;
        } else {
            return 'Daily, starting at '. $startAtFormatted. '<br /> in batches of ' . $this->getBatchLimit();
        }
    }

    public function getTimeFormatted($startAt = 0)
    {
        $dateTime = new Zend_Date();
        $dateTime->setTime($startAt . ':00:00');

        $format = Mage::app()->getLocale()->getTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        return $dateTime->toString($format);
    }
}
