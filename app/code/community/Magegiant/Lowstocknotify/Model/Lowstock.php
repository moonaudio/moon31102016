<?php
/**
 * MageGiant
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magegiant.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magegiant.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @copyright   Copyright (c) 2014 Magegiant (http://magegiant.com/)
 * @license     http://magegiant.com/license-agreement.html
 */


class Magegiant_Lowstocknotify_Model_Lowstock extends Mage_Core_Model_Abstract{

	public function getLowStockProducts(){

		$configManageStock = (int) Mage::getStoreConfigFlag(
			Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
		$globalNotifyStockQty = (float) Mage::getStoreConfig(
			Mage_CatalogInventory_Model_Stock_Item::XML_PATH_NOTIFY_STOCK_QTY);

		/* @var $product Mage_Catalog_Model_Product */
		$product = Mage::getModel('catalog/product');
		/* @var $collection Mage_Catalog_Model_Resource_Product_Collection */

		$collection = $product->getCollection();
		$stockItemTable = $collection->getTable('cataloginventory/stock_item');

		$stockItemWhere = '({{table}}.low_stock_date is not null) '
			. " AND ( ({{table}}.use_config_manage_stock=1 AND {$configManageStock}=1)"
			. " AND {{table}}.qty < "
			. "IF({$stockItemTable}.`use_config_notify_stock_qty`, {$globalNotifyStockQty}, {{table}}.notify_stock_qty)"
			. ' OR ({{table}}.use_config_manage_stock=0 AND {{table}}.manage_stock=1) )';

		$collection
			->addAttributeToSelect('name', true)
			->joinTable('cataloginventory/stock_item', 'product_id=entity_id',
				array(
					'qty'=>'qty',
					'notify_stock_qty'=>'notify_stock_qty',
					'use_config' => 'use_config_notify_stock_qty',
					'low_stock_date' => 'low_stock_date'),
				$stockItemWhere, 'inner')
			->setOrder('low_stock_date');

		$collection->addAttributeToFilter('status',
			array('in' => Mage::getSingleton('catalog/product_status')->getVisibleStatusIds()));

		$result = array();
		foreach($collection as $_product){
			$result[] = array(
				'id'=>$_product->getId(),
				'sku'=>$_product->getSku(),
				'name'=>$_product->getName(),
				'qty'=>$_product->getQty(),
				'status'=>$_product->getStatus(),
				'updated_at'=>$_product->getUpdatedAt(),
				'low_stock_date'=>$_product->getLowStockDate(),

			);
		}

		return $result;
	}
}