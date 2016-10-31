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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Import_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Add fieldset
     *
     * @return Mage_ImportExport_Block_Adminhtml_Import_Edit_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/startImport'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));
        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('importexport')->__('Import Settings')));
        $fieldset->addField('entity', 'select', array(
            'name'     => 'entity',
            'title'    => Mage::helper('googlebasefeedgenerator')->__('Entity Type'),
            'label'    => Mage::helper('googlebasefeedgenerator')->__('Entity Type'),
            'required' => true,
            'values'   => Mage::getModel('googlebasefeedgenerator/source_import')->toOptionArray(),
            'after_element_html' => '<br/> Reserved for the future use, in case we decide to use other data export types.',
        ));
        $fieldset->addField(Mage_ImportExport_Model_Import::FIELD_NAME_SOURCE_FILE, 'file', array(
            'name'     => Mage_ImportExport_Model_Import::FIELD_NAME_SOURCE_FILE,
            'label'    => Mage::helper('googlebasefeedgenerator')->__('Select File to Import'),
            'title'    => Mage::helper('googlebasefeedgenerator')->__('Select File to Import'),
            'required' => true,
            'after_element_html' => '<br/> Please click the following link to download a sample import file.
                <a href="' . $this->getUrl('*/*/exportSample') . '">Sample Data</a>',
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}