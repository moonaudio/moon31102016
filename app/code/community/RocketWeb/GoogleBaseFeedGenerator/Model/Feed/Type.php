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

class RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Type extends Varien_Object
{
    const TYPE_GENERIC          = 'generic';
    const TYPE_GOOGLE_SHOPPING  = 'google_shopping';
    const TYPE_GOOGLE_INVENTORY = 'google_inventory';
    const TYPE_GOOGLE_PROMO     = 'google_promo';
    const TYPE_BING             = 'bing';
    const TYPE_SHAREASALE       = 'shareasale';
    const TYPE_AMAZON           = 'amazon';
    const TYPE_EBAY             = 'ebay';
    const TYPE_JET              = 'jet';
    const TYPE_SHOPPING         = 'shopping';
    const TYPE_GETPRICE         = 'getprice';
    const TYPE_NETXTAG          = 'nextag';
    const TYPE_THEFIND          = 'thefind';
    const TYPE_SHOPMANIA        = 'shopmania';
    const TYPE_SHOPZILLA        = 'shopzilla';
    const TYPE_SEARS            = 'sears';
    const TYPE_ALIEXPRESS       = 'aliexpress';

    static public function getOptionArray()
    {
        $options = array(
            self::TYPE_GOOGLE_SHOPPING  => Mage::helper('googlebasefeedgenerator')->__('Google Shopping Feed'),
            self::TYPE_GOOGLE_INVENTORY => Mage::helper('googlebasefeedgenerator')->__('Google Inventory'),
            self::TYPE_GOOGLE_PROMO     => Mage::helper('googlebasefeedgenerator')->__('Google Promotions'),
            self::TYPE_BING             => Mage::helper('googlebasefeedgenerator')->__('Bing'),
            self::TYPE_SHAREASALE       => Mage::helper('googlebasefeedgenerator')->__('ShareASale'),
            self::TYPE_AMAZON           => Mage::helper('googlebasefeedgenerator')->__('Amazon'),
            self::TYPE_EBAY             => Mage::helper('googlebasefeedgenerator')->__('Ebay EEAN'),
            self::TYPE_JET              => Mage::helper('googlebasefeedgenerator')->__('Jet.com'),
            self::TYPE_SHOPPING         => Mage::helper('googlebasefeedgenerator')->__('Shopping.com'),
            self::TYPE_GETPRICE         => Mage::helper('googlebasefeedgenerator')->__('Getprice.com'),
            self::TYPE_NETXTAG          => Mage::helper('googlebasefeedgenerator')->__('Nextag.com'),
            self::TYPE_THEFIND          => Mage::helper('googlebasefeedgenerator')->__('Thefind.com'),
            self::TYPE_SHOPMANIA        => Mage::helper('googlebasefeedgenerator')->__('Shopmania.com'),
            self::TYPE_SHOPZILLA        => Mage::helper('googlebasefeedgenerator')->__('Shopzilla.com'),
            self::TYPE_SEARS            => Mage::helper('googlebasefeedgenerator')->__('Sears.com'),
            self::TYPE_ALIEXPRESS       => Mage::helper('googlebasefeedgenerator')->__('Aliexpress.com'),
            self::TYPE_GENERIC          => Mage::helper('googlebasefeedgenerator')->__('Generic'),
        );
        foreach ($options as $type => $label) {
            $file = Mage::getModuleDir('etc', 'RocketWeb_GoogleBaseFeedGenerator'). DS. 'feeds'. DS. $type. '.xml';
            if (!is_readable($file)) {
                unset($options[$type]);
            }
        }
        return $options;
    }

    static public function getTaxonomyFeedTypes()
    {
        return array(
            self::TYPE_GOOGLE_SHOPPING    => Mage::helper('googlebasefeedgenerator')->__('Google Shopping'),
        );
    }

    static public function getTaxonomyFeedUrl()
    {
        return array(
            self::TYPE_GOOGLE_SHOPPING    => 'http://www.google.com/basepages/producttype/taxonomy.%s.txt'
        );
    }

    static public function getLabel($type)
    {
        $options = self::getOptionArray();
        return array_key_exists($type, $options) ? $options[$type] : ucwords(str_replace('_', ''));
    }
}