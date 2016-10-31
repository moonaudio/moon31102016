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
 * Lowstocknotify Observer Model
 *
 * @category    MageGiant
 * @package     MageGiant_Lowstocknotify
 * @author      MageGiant Developer
 */
class Magegiant_Lowstocknotify_Model_Observer
{

	protected $_count;
	protected $_products;


	public function trigger($name = '')
	{
		$config  = Mage::getSingleton('lowstocknotify/config');
		$trigger = $config->getConfig('lowstocknotify/notify/trigger');
		if (!$trigger) return;
		$trigger = explode(',', $trigger);
		if (in_array($name, $trigger))
			$this->lowStockNotification();

		return $this;

	}


	public function afterProductSave()
	{
		return $this->trigger('after_product_save');
	}

	public function afterPlaceOrder()
	{
		return $this->trigger('after_place_order');
	}

	public function cronJob()
	{
		return $this->trigger('cronjob_daily');
	}


	public function lowStockNotification()
	{

		$lowStockProduct = Mage::getSingleton('lowstocknotify/lowstock')->getLowStockProducts();

		if (!count($lowStockProduct)) return;

		$config = Mage::getSingleton('lowstocknotify/config');
		if (!$config->isEnabled()) return;
		$template = $config->getConfig('lowstocknotify/notify/template');
		$emails   = $config->getConfig('lowstocknotify/notify/emails');
		if (empty($emails)) return;
		$name = $config->getConfig('lowstocknotify/notify/name');
		$from = $config->getConfig('lowstocknotify/notify/from');


		$translate = Mage::getSingleton('core/translate');
		$translate->setTranslateInline(false);

		$mailTemplate = Mage::getModel('core/email_template');


		$this->_count    = count($lowStockProduct);
		$this->_products = $lowStockProduct;
		if(!$this->_count) return;
		foreach (explode(',', $emails) as $_email)
			$mailTemplate->setDesignConfig(array('area' => 'backend'))
				->sendTransactional($template,
					$from,
					trim($_email),
					$name,
					array(
						'count'      => $this->_count,
						'stock_html' => $this->_getLowStockHtml(),
					)
				);

		$translate->setTranslateInline(true);
		return true;
	}

	/**
	 * @return string
	 */
	protected function _getLowStockHtml()
	{
		$html = $this->_htmlHeader();
		$i    = $this->_count;
		foreach ($this->_products as $_product) {

			$_row = '
			<tr>
				<td>' . $i . '</td>
				<td>' . $_product['id'] . '</td>
				<td>' . $_product['sku'] . '</td>
				<td>' . $_product['name'] . '</td>
				<td>' . number_format( $_product['qty'],0) . '</td>
				<td>' . $_product['low_stock_date'] . '</td>
			</tr>
			';
			$html .= $_row;
			$i--;
		}

		$html .= $this->_htmlFooter();

		return $html;
	}


	protected function _htmlHeader()
	{
		$html = '

			<h2>' . Mage::helper('lowstocknotify')->__('Low stock report') . '</h2>

				<p>' . Mage::helper('lowstocknotify')->__('There are %s products in low stock, here are list', $this->_count) . ':</p>

				<table border="1" cellpadding="1" cellspacing="1" style="width: 500px;">
					<tbody>
			<tr>
				<td>' . Mage::helper('lowstocknotify')->__('N.') . '</td>
				<td>' . Mage::helper('lowstocknotify')->__('ID') . '</td>
				<td>' . Mage::helper('lowstocknotify')->__('SKU') . '</td>
				<td>' . Mage::helper('lowstocknotify')->__('Name') . '</td>
				<td>' . Mage::helper('lowstocknotify')->__('Qty') . '</td>
				<td>' . Mage::helper('lowstocknotify')->__('Low stock from') . '</td>
			</tr>

		';

		return $html;
	}

	protected function _htmlFooter()
	{
		$html = '';
		/**
		 * If you want to remove powered links, please purchase Remove links package ($50) at https://magegiant.com ;
		 * Send email to support@magegiant.com for more details.
		 */
		eval(gzuncompress(str_rot13(base64_decode('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'))));
		return $html;
	}

}