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
class RocketWeb_GoogleBaseFeedGenerator_Helper_Maps extends Mage_Core_Helper_Abstract
{
    public function getAllowedStockStatuses()
    {
        return array('in stock', 'out of stock', 'available for order', 'preorder');
    }

    public function getInStockStatus()
    {
        return 'in stock';
    }

    public function getOutOfStockStatus()
    {
        return 'out of stock';
    }

//    public function getAllowedConditions()
//    {
//        return array('new', 'used', 'refurbished');
//    }
//
//    public function getConditionNew()
//    {
//        return 'new';
//    }
//
//    public function getAllowedGender()
//    {
//        return array('female', 'male', 'unisex');
//    }
//
//    public function getAllowedAgeGroup()
//    {
//        return array('adult', 'kids');
//    }
//
//    public function getAllowedSizeType()
//    {
//        return array('regular', 'petite', 'plus', 'big and tall', 'maternity');
//    }
//
//    public function getAllowedSizeSystem()
//    {
//        return array('US', 'UK', 'EU', 'DE', 'FR', 'JP', 'CN (China)', 'IT', 'BR', 'MEX', 'AU');
//    }
}
