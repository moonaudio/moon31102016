<?php

class Magegiant_Lowstocknotify_IndexController extends Mage_Core_Controller_Front_Action
{

	public function indexAction()
	{
	}

	public function cronAction()
	{
		$observer = Mage::getModel('lowstocknotify/observer');
		$observer->lowStockNotification();
		echo 'Done';
	}
}