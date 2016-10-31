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

class RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Ftp extends Mage_Core_Model_Abstract
{
    const OBSCURED_VALUE = '******';

    /**
     * Internal constructor
     */
    protected function _construct()
    {
        $this->_init('googlebasefeedgenerator/feed_ftp');
    }

    /**
     * Processing object before save data. Encrypt password
     *
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Ftp
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $password = $this->getPassword();
        if ($password == self::OBSCURED_VALUE) {
            $password = $this->getOrigData('password');
        } else {
            $password = Mage::helper('core')->encrypt($password);
        }
        $this->setPassword($password);
        return $this;
    }
}
