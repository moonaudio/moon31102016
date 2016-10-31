<?php

/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Test_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function getFormHtml()
    {
        $html = parent::getFormHtml();
        $html .= "<script type='text/javascript'>
window.onload = function() {
$('btn_test_feed').observe('click', function (e) {
    if ($('sku').value != '') $('loading-mask').toggle(true);
});
}
</script>";
        return $html;
    }
    /**
     * Setup form fields for inserts/updates
     *
     * return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getUrl('*/*/test', array('id' => $this->getRequest()->getParam('id'))),
            'method'    => 'post'
        ));


        $fieldset = $form->addFieldset('input', array(
            'legend'    => Mage::helper('googlebasefeedgenerator')->__('Which product ?'),
        ));

        $fieldset->addField('sku', 'text', array(
            'name'      => 'sku',
            'label'     => Mage::helper('googlebasefeedgenerator')->__('SKU or ID'),
            'required'  => true,
            'after_element_html' => Mage::helper('googlebasefeedgenerator')->__('<br />SKU here must be is visible in catalog and enabled.
                                                                                 <br />If you want to test a sub-item, you must fill in the parent SKU here.')
        ));

        $feed = Mage::registry('googlebasefeedgenerator_feed');

        if ($this->getRequest()->isPost()) {
            $form->setValues($this->getRequest()->getParams());
        } else {
            $form->setValues(array('store_id' => $feed->getStoreId()));
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}