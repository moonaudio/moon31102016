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
 * Lowstocknotify Status Model
 *
 * @category    MageGiant
 * @package     MageGiant_Lowstocknotify
 * @author      MageGiant Developer
 */
class Magegiant_Lowstocknotify_Model_System_Config_Trigger extends Varien_Object
{

	/**
	 * get model option as array
	 *
	 * @return array
	 */
	static public function getOptionArray()
	{
		return array(
			'after_product_save' => Mage::helper('lowstocknotify')->__('After Product Save'),
			'after_place_order'  => Mage::helper('lowstocknotify')->__('After Place Order'),
			'cronjob_daily'      => Mage::helper('lowstocknotify')->__('Run Daily'),
		);
	}

	/**
	 * get model option hash as array
	 *
	 * @return array
	 */
	static public function getOptionHash()
	{
		$options = array();
		foreach (self::getOptionArray() as $value => $label) {
			$options[] = array(
				'value' => $value,
				'label' => $label
			);
		}

		return $options;
	}

	public function toOptionArray()
	{
		return self::getOptionHash();
	}
}