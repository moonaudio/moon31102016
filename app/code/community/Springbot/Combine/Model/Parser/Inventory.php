<?php

class Springbot_Combine_Model_Parser_Inventory extends Springbot_Combine_Model_Parser
{
	protected $_inventory;
	protected $_accessor = '_inventory';

	public function __construct(Mage_CatalogInventory_Model_Stock_Item $inventory)
	{
		$this->_inventory = $inventory;
	}

	protected function _parse()
	{
		$productId = $this->_inventory->getProductId();
		if ($product = Mage::getModel('catalog/product')->load($productId)) {
			$this->_data = array(
				'product_id' => $this->_inventory->getProductId(),
				'system_managed' => (bool) $this->_inventory->getManageStock(),
				'out_of_stock_qty' => $this->_inventory->getMinQty(),
				'quantity' => $this->_inventory->getQty(),
				'store_id' => $this->getSpringbotStoreId(),
				'item_id' => $this->_inventory->getItemId(),
				'is_in_stock' => $this->_inventory->getIsInStock(),
				'min_sale_qty' => $this->_inventory->getMinSaleQty(),
				'sku' => $this->_getSku($product),
				'sku_fulfillment' => $product->getSku(),
			);

			return parent::_parse();
		}
	}

	public function getSpringbotStoreId()
	{
		return $this->_getSpringbotStoreId($this->getMageStoreId());
	}

	protected function _getSku($product)
	{
		$parents = Mage::helper('combine/parser')->getParentSkus($product->getId());
		if(sizeof($parents) > 0) {
			$sku = implode('|', $parents);
		} else {
			$sku = $this->_getSkuFailsafe($product);
		}
		return $sku;
	}

}
