<?php

class Springbot_Services_Tasks_HarvestInventory extends Springbot_Services
{
	public function run()
	{
		$this->_turnOnInventorySync();

		Springbot_Boss::scheduleJob(
			'cmd:harvest',
			array(
				's' => $this->getStoreId(),
				'c' => 'inventories',
			),
			Springbot_Services::HARVEST,
			'default',
			$this->getStoreId()
		);
		return true;
	}

	protected function _turnOnInventorySync()
	{
		if(!Mage::getStoreConfig('springbot/advanced/send_inventory') == 1) {
			Mage::getConfig()->saveConfig('springbot/advanced/send_inventory', 1, 'default', 0);
			Mage::getConfig()->cleanCache();
		}
	}
}
