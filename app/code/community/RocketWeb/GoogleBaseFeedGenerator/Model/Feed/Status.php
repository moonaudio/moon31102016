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

class RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status extends Varien_Object
{
    const STATUS_DISABLED       = 0;
    const STATUS_SCHEDULED      = 1;
    const STATUS_PENDING        = 2;
    const STATUS_PROCESSING     = 3;
    const STATUS_COMPLETED      = 4;
    const STATUS_ERROR          = 5;

    /**
     * Load object data
     *
     * @param   RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed
     * @return  RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Status
     */
    public function load(RocketWeb_GoogleBaseFeedGenerator_Model_Feed $feed)
    {
        $code  = $feed->getData('status');
        $label = self::getStatusesOptions($code);
        if ($code == self::STATUS_PROCESSING) {
            $label = $feed->getMessage('progress'). '%';
        }
        $this->setData(array('code' => $code, 'label' => $label));
        return $this;
    }

    static public function getStatusesOptions($value = null)
    {
        $statuses = array(
            self::STATUS_DISABLED            => Mage::helper('index')->__('Disabled'),
            self::STATUS_SCHEDULED           => Mage::helper('index')->__('Scheduled'),
            self::STATUS_PROCESSING          => Mage::helper('index')->__('Processing'),
            self::STATUS_COMPLETED           => Mage::helper('index')->__('Completed'),
            self::STATUS_PENDING             => Mage::helper('index')->__('Pending'),
            self::STATUS_ERROR               => Mage::helper('index')->__('Error'),
        );

        if (!is_null($value)) {
            return (array_key_exists($value, $statuses)) ? $statuses[$value] : '';
        }

        return $statuses;
    }

}