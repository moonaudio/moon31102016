<?php

class Springbot_Services_Harvest_Inventories extends Springbot_Services_Harvest
{
	public function run()
	{
		if ($this->_sendInventory()) {
			$collection = $this->getCollection($this->getStoreId());
			$api = Mage::getModel('combine/api');
			$harvester = new Springbot_Combine_Model_Harvest_Inventories($api, $collection, $this->getDataSource());
			$harvester->setStoreId($this->getStoreId());
			$harvester->harvest();

			return $this->reportCount($harvester);
		}
	}

	public function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('cataloginventory/stock_item')->getCollection();

		if ($this->getStartId() !== null) {
			$collection->addFieldToFilter('item_id', array('gt' => $this->getStartId()));
		}
		if ($this->getStopId()) {
			$collection->addFieldToFilter('item_id', array('lteq' => $this->getStopId()));
		}

		if ($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}

		return $collection;
	}

	private function _sendInventory() {
		if (Mage::getStoreConfig('springbot/advanced/send_inventory') == 1) {
			return true;
		} else {
			return false;
		}
	}
}
