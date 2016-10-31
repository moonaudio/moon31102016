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

class VladimirPopov_WebForms_Block_Adminhtml_Webforms_Edit_Tab_Information
	extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareLayout(){
		
		parent::_prepareLayout();
	}	
	
	protected function _prepareForm()
	{
		$model = Mage::getModel('webforms/webforms');
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('webforms_form',array(
			'legend' => Mage::helper('webforms')->__('Form Information')
		));
		
		$fieldset->addField('name','text',array(
			'label' => Mage::helper('webforms')->__('Name'),
			'class' => 'required-entry',
			'required' => true,
			'name' => 'name'
		));
		
		$fieldset->addField('description','editor',array(
			'label' => Mage::helper('webforms')->__('Description'),
			'required' => false,
			'name' => 'description',
			'style' => 'height:20em; width:50em;',
			'note' => Mage::helper('webforms')->__('This text will appear under the form name'),
			'wysiwyg' => true,
		));
		
		$fieldset->addField('success_text','editor',array(
			'label' => Mage::helper('webforms')->__('Success text'),
			'required' => false,
			'name' => 'success_text',
			'style' => 'height:20em; width:50em;',
			'note' => Mage::helper('webforms')->__('This text will be displayed after the form completion'),
			'wysiwyg' => true,
		));
		
		$fieldset->addField('is_active', 'select', array(
			'label'     => Mage::helper('webforms')->__('Status'),
			'title'     => Mage::helper('webforms')->__('Status'),
			'name'      => 'is_active',
			'required'  => true,
			'options'   => $model->getAvailableStatuses(),
		));
		
		if (!$model->getId()) {
			$model->setData('is_active', $isElementDisabled ? '0' : '1');
		}
		
		if(Mage::getSingleton('adminhtml/session')->getWebFormsData())
		{
			$form->setValues(Mage::getSingleton('adminhtml/session')->getWebFormsData());
			Mage::getSingleton('adminhtml/session')->setWebFormsData(null);
		} elseif(Mage::registry('webforms_data')){
			$form->setValues(Mage::registry('webforms_data')->getData());
		}
		return parent::_prepareForm();
	}
}  
?>
