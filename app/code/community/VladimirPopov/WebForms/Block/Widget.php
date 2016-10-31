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

class VladimirPopov_WebForms_Block_Widget
	extends Mage_Core_Block_Template
	implements Mage_Widget_Block_Interface
{
	protected function _prepareLayout()
	{
		$data = $this->getData();
		//get form data
		$webform = Mage::getModel('webforms/webforms')->load($data['webform_id']);
		Mage::register('webform',$webform);
		
		if($webform->getSurvey()){
			$collection = Mage::getModel('webforms/results')->getCollection();
			
			if(Mage::helper('customer')->isLoggedIn())
				$collection->addFilter('webform_id',$data['webform_id'])->addFilter('customer_id',Mage::getSingleton('customer/session')->getCustomerId());
			else{
				$session_validator = Mage::getSingleton('customer/session')->getData('_session_validator_data');
				$collection->addFilter('customer_ip',ip2long($session_validator['remote_addr']));
			}
			$count = $collection->count();
			if($count>0){
				$show_success = true;
			}
		}
		
		if(Mage::getSingleton('core/session')->getWebformsSuccess() == $data['webform_id'] || $show_success){
			Mage::register('show_success',true);
			Mage::getSingleton('core/session')->setWebformsSuccess();
		}
		
		if($webform->getRegisteredOnly() && !Mage::helper('customer')->isLoggedIn()){
			Mage::getSingleton('customer/session')->setBeforeAuthUrl($this->getRequest()->getRequestUri());
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::helper('customer')->getLoginUrl(),301);
		}
		
		Mage::register('fields_to_fieldsets',$webform->getFieldsToFieldsets());
		
		//use captcha
		if(!Mage::helper('customer')->isLoggedIn()){
			$pubKey = Mage::getStoreConfig('webforms/captcha/public_key');
			$privKey = Mage::getStoreConfig('webforms/captcha/private_key');
			if($pubKey && $privKey)
				Mage::register('use_captcha',true);
		}
		
		//proccess the result
		if($this->getRequest()->getParam('submitWebform_'.$data['webform_id'])){
			//validate captcha
			if(Mage::registry('use_captcha')){
				if($this->getRequest()->getParam('recaptcha_response_field')) {
					$verify = $this->getCaptcha()->verify($this->getRequest()->getParam('recaptcha_challenge_field'),$this->getRequest()->getParam('recaptcha_response_field'));
					if($verify->isValid()){
						$success = $this->saveResult();
					} else {
						Mage::getSingleton('core/session')->addError($this->__('Verification code was not correct. Please try again.'));
					}
				} else {
					Mage::getSingleton('core/session')->addError($this->__('Verification code was not correct. Please try again.'));
				}
			} else {
				$success = $this->saveResult();
			}
			if($success){
				Mage::getSingleton('core/session')->setWebformsSuccess($data['webform_id']);
			}
			Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::helper('core/url')->getCurrentUrl());
		}
		parent::_prepareLayout();
		
	}
	
	public function getCaptcha(){
		$pubKey = Mage::getStoreConfig('webforms/captcha/public_key');
		$privKey = Mage::getStoreConfig('webforms/captcha/private_key');
		if($pubKey && $privKey)
			$recaptcha = new Zend_Service_ReCaptcha($pubKey, $privKey);
		return $recaptcha;
	}
	
	public function saveResult(){
		if(!Mage::registry('webform')) return false;
		try{
			$postData = $this->getRequest()->getPost();
			$resultsModel = Mage::getModel('webforms/results');
			
			$session_validator = Mage::getSingleton('customer/session')->getData('_session_validator_data');
			$resultsModel->setData($postData)
				->setWebformId(Mage::registry('webform')->getId())
				->setStoreId(Mage::app()->getStore()->getId())
				->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId())
				->setCustomerIp(ip2long($session_validator['remote_addr']))
				->save();
			$emailSettings = Mage::registry('webform')->getEmailSettings();
			
			if($emailSettings['email_enable']){
				
				$result = Mage::getModel('webforms/results')->load($resultsModel->getId());
				$result->sendEmail();
				if(Mage::registry('webform')->getDuplicateEmail()){
					$result->sendEmail('customer');
				}
			}
			return true;
		} catch (Exception $e){
			Mage::getSingleton('core/session')->addError($e->getMessage());
			return false;
		}
	}
	
}
  
?>
