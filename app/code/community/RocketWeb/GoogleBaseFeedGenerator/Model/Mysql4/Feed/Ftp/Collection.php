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

class RocketWeb_GoogleBaseFeedGenerator_Model_Mysql4_Feed_Ftp_Collection 
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Define resource model
     */
    protected function _construct() {
        $this->_init('googlebasefeedgenerator/feed_ftp');
    }

    /**
     * Adds feed ID to filter
     * 
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Mysql4_Feed_Ftp_Collection
     */
    public function addFeedFilter($feedId)
    {
        $this->addFieldToFilter('feed_id', $feedId);
        return $this;
    }
}
