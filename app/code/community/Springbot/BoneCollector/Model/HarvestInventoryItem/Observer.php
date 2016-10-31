<?php

class Springbot_BoneCollector_Model_HarvestInventoryItem_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	protected $_product;

	protected $_attributes = array(
		'qty'
	);


	public function onCatalogInventorySave(Varien_Event_Observer $observer)
	{
		if ($this->_sendInventory()) {
			try {
				$event = $observer->getEvent();
				$_item = $event->getItem();
				if ((int)$_item->getData('qty') != (int)$_item->getOrigData('qty')) {
					Springbot_Boss::scheduleJob('post:inventory', array(
							'i' => $_item->getItemId(),
						), Springbot_Services::LISTENER, 'listener'
					);
				}
			}
			catch (Exception $e) {
				Springbot_Log::error($e);
			}
		}
	}

	public function onQuoteSubmit(Varien_Event_Observer $observer)
	{
		if ($this->_sendInventory()) {
			$quote = $observer->getEvent()->getQuote();
			foreach ($quote->getAllItems() as $item) {
				try {
					Springbot_Boss::scheduleJob('post:inventory', array(
							'i' => $item->getItemId(),
						), Springbot_Services::LISTENER, 'listener'
					);
				}
				catch (Exception $e) {
					Springbot_Log::error($e);
				}
			}
		}
	}

	public function onCancelOrderItem(Varien_Event_Observer $observer)
	{
		if ($this->_sendInventory()) {
			try {
				$item = $observer->getEvent()->getItem();
				Springbot_Boss::scheduleJob('post:inventory', array(
						'i' => $item->getItemId(),
					), Springbot_Services::LISTENER, 'listener'
				);
			}
			catch (Exception $e) {
				Springbot_Log::error($e);
			}
		}
	}

	public function onCreditmemoSave(Varien_Event_Observer $observer)
	{
		if ($this->_sendInventory()) {
			try {
				$event = $observer->getEvent();
				$creditmemo = $event->getCreditmemo();
				foreach ($creditmemo->getAllItems() as $creditMemoItem) {
					$productId = $creditMemoItem->getProductId();
					if ($product = Mage::getModel('catalog/product')->load($productId)) {
						if ($inventoryItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)) {
							Springbot_Boss::scheduleJob('post:inventory', array(
									'i' => $inventoryItem->getItemId(),
								), Springbot_Services::LISTENER, 'listener'
							);
						}
					}
				}
			}
			catch (Exception $e) {
				Springbot_Log::error($e);
			}
		}

	}

	private function _sendInventory() {
		if (Mage::getStoreConfig('springbot/advanced/send_inventory') == 1) {
			return true;
		} else {
			return false;
		}
	}




}
