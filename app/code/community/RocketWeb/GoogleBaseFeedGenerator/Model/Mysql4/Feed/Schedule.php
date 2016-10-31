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

class RocketWeb_GoogleBaseFeedGenerator_Model_Mysql4_Feed_Schedule extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize resource model
     */
    protected function _construct() {
        $this->_init('googlebasefeedgenerator/feed_schedule', 'id');
    }

    /**
     * Get all start_at hours, which are in already in use
     * 
     * @return array
     */
    public function getAllStartAt()
    {
        $select = $this->getReadConnection()->select()
            ->from($this->getMainTable(), array('start_at'))
            ->group('start_at');

        return $this->getReadConnection()->fetchCol($select);
    }
}
