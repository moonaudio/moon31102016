<?php

class Springbot_Combine_Model_Harvest_Inventories extends Springbot_Combine_Model_Harvest
{
	protected $_segmentSize = 100;

	public function getMageModel()
	{
		return 'cataloginventory/stock_item';
	}

	public function getParserModel()
	{
		return 'combine/parser_inventory';
	}

	public function getApiController()
	{
		return 'inventories';
	}

	public function getApiModel()
	{
		return 'inventories';
	}

	public function getRowId()
	{
		return 'item_id';
	}

	/**
	 * Parse caller for dependent parser method
	 *
	 * @param Mage_Core_Model_Abstract $model
	 * @return Zend_Json_Expr
	 */
	public function parse($model)
	{
		$parser = Mage::getModel($this->getParserModel(), $model);
		$parser->setMageStoreId($this->getStoreId());
		$parser->parse();

		if ($this->getDelete()) {
			$parser->setIsDeleted(true);
		}

		return $parser->getData();
	}

	public function loadMageModel($entityId)
	{
		$model = Mage::getModel($this->getMageModel());
		$model->load($entityId);
		return $model;
	}
}


