<?php
/**
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */


/**
 * Adminhtml Google Content Captcha challenge
 *
 * @category	Exinent
 * @package    Exinent_GoogleShoppingApi
 */
class Exinent_GoogleShoppingApi_Block_Adminhtml_Captcha extends Mage_Adminhtml_Block_Template
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('googleshoppingapi/captcha.phtml');
    }

    /**
     * Get HTML code for confirm captcha button
     *
     * @return string
     */
    public function getConfirmButtonHtml()
    {
        $confirmButton = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => $this->__('Confirm'),
                'onclick'   => "if($('user_confirm').value != '')
                                {
                                    setLocation('".$this->getUrl('*/*/confirmCaptcha', array('_current'=>true))."' + 'user_confirm/' + $('user_confirm').value + '/');
                                }",
                'class'     => 'task'
            ));
        return $confirmButton->toHtml();
    }
}
