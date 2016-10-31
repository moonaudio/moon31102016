<?php

/**
 * Magebird.com
 *
 * @category   Magebird
 * @package    Magebird_Popup
 * @copyright  Copyright (c) 2014 Magebird (http://www.Magebird.com)
 * @license    http://www.magebird.com/licence
 * Any form of ditribution, sell, transfer forbidden see licence above 
 */
 
class Magebird_Popup_Adminhtml_Magebird_MousetrackingController extends Mage_Adminhtml_Controller_Action
{

  	protected function _initAction() {
  		$this->loadLayout();
  		return $this;
  	}   
  
    public function indexAction()
    {
      $this->_initAction();   			
      $block = $this->getLayout()->createBlock('magebird_popup/mousetracking')->setTemplate('magebird/popup/mousetracking.phtml');          
      $this->getResponse()->setBody($block->toHtml()); 
    }
              
} 