<?php
/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement/
 */


class Magegiant_Lowstocknotify_Model_Config extends Mage_Core_Model_Abstract{

    /**
     * Magegiant
     * Every config should be defined as const here
     */
    const XML_GENERAL_ENABLE = 'lowstocknotify/general/enable';

    public function isEnabled(){
        return $this->getConfig(self::XML_GENERAL_ENABLE);
    }

    /**
     * @param null $name
     * @return mixed|null
     */
    public function getConfig($name = null){
        if(!$name) return null;
        return Mage::getStoreConfig($name,Mage::app()->getStore()->getId());
    }
}