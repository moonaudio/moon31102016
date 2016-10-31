<?php
/**
 * MageGiant
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MageGiant.com license that is
 * available through the world-wide-web at this URL:
 * http://magegiant.com/license-agreement/
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    MageGiant
 * @package     MageGiant_Lowstocknotify
 * @copyright   Copyright (c) 2014 MageGiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement/
 */

/**
 * Lowstocknotify Helper
 * 
 * @category    MageGiant
 * @package     MageGiant_Lowstocknotify
 * @author      MageGiant Developer
 */
class Magegiant_Lowstocknotify_Helper_Data extends Mage_Core_Helper_Abstract
{
 
 	protected function _initConfig()
    {
        return Mage::getSingleton('lowstocknotify/config');
    }

    public function isEnabled(){
        return $this->_initConfig()->isEnabled();
    }

}