<?php

class Springbot_Services_Post_Inventory extends Springbot_Services_Post
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Inventories($api, $collection, $this->getDataSource());
		$harvester->setDelete($this->getDelete());
		$this->_aggregateInventoryItem($this->getStartId(), $harvester);
		$harvester->postSegment();
	}



	protected function _aggregateInventoryItem($itemId, $harvester)
	{
		$inventoryItem = Mage::getModel('cataloginventory/stock_item')->load($itemId);
		$productId = $inventoryItem->getProductId();
		if ($product = Mage::getModel('catalog/product')->load($productId)) {
			foreach ($product->getStoreIds() as $storeId) {
				$inventoryItem->setStoreId($storeId);
				$harvester->push($inventoryItem);
			}
		}
	}

}
