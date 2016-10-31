<?php
/**
 * Feel free to contact me via Facebook
 * http://www.facebook.com/rebimol
 *
 *
 * @author 		Vladimir Popov
 * @copyright  	Copyright (c) 2011 Vladimir Popov
 * @license    	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class VladimirPopov_WebForms_Block_Webforms extends VladimirPopov_WebForms_Block_Widget
{
	public function getData(){
		$data = $this->getRequest()->getParams();
		$data['webform_id'] = $data['id'];
		return $data;
	}
	
	public function _prepareLayout(){
		parent::_prepareLayout();
		$this->getLayout()->getBlock('head')->setTitle(Mage::registry('webform')->getName());
		Mage::register('show_form_name',true);
	}
	
}
?>
