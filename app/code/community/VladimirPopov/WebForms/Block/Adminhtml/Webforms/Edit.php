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

class VladimirPopov_WebForms_Block_Adminhtml_Webforms_Edit 
	extends Mage_Adminhtml_Block_Widget_Form_Container
{
	protected function _prepareLayout(){
		parent::_prepareLayout();
		if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
			 $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
		}
	}

	public function __construct(){
		parent::__construct();
		$this->_objectId = 'id';
		$this->_blockGroup = 'webforms';
		$this->_controller = 'adminhtml_webforms';
		
		if(Mage::registry('webforms_data') && Mage::registry('webforms_data')->getId() ){
			$this->_addButton('add_fieldset', array(
				'label' => Mage::helper('webforms')->__('Add Field Set'),
				'class' => 'add',
				'onclick'   => 'setLocation(\'' . $this->getAddFieldsetUrl() . '\')',
			));
			
			$this->_addButton('add_field', array(
				'label' => Mage::helper('webforms')->__('Add Field'),
				'class' => 'add',
				'onclick'   => 'setLocation(\'' . $this->getAddFieldUrl() . '\')',
			));
		} else {
			$this->_removeButton('save');
		}
		
		$this->_addButton('saveandcontinue', array(
			'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
			'onclick'   => 'saveAndContinueEdit()',
			'class'     => 'save',
		), -100);
		
		$this->_formScripts[] = "
			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		";
	}
	
	public function getAddFieldUrl()
	{
		return $this->getUrl('*/adminhtml_fields/edit', array('webform_id'=>Mage::registry('webforms_data')->getId()));
	}
	
	public function getAddFieldsetUrl()
	{
		return $this->getUrl('*/adminhtml_fieldsets/edit', array('webform_id'=>Mage::registry('webforms_data')->getId()));
	}
	
	public function getHeaderText(){
		if( Mage::registry('webforms_data') && Mage::registry('webforms_data')->getId() ) {
			return Mage::helper('webforms')->__("Edit '%s' Form", $this->htmlEscape(Mage::registry('webforms_data')->getName()));
		} else {
			return Mage::helper('webforms')->__('Add Form');
		}
	}
}  
?>
