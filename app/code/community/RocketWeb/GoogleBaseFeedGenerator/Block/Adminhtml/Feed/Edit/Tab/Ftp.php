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
/* Class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Ftp
 * 
 * @method getAccounts() array Returns ftp accounts array
 */
class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Ftp
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract
{
    const DEFAULT_COLUMNS_NUMBER = 6;

    /**
     * Constructor. Set template and feed
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('googlebasefeedgenerator/grid/ftp.phtml');
        $feed = Mage::registry('googlebasefeedgenerator_feed');
        if (!$feed || ($feed && !$feed->getId())) {
            if (!$feed) {
                $feed = Mage::getModel('googlebasefeedgenerator/feed')->load(0);
            }
        }
        $this->setFeed($feed);
        /**
         * All the auxiliary fields specified in feed config default_feed_config -> ftp
         * Adding new field should implement public methods getFieldNameHtml, getFieldNameValue
         * Be aware that field name in method name should be camel caze, first letter also capitalized,
         * for example, path => getPathHtml, additional_data => getAdditionalDataHtml
         * @var array 
         */
        $_auxiliaryFields = array();
        if ($feed->hasData('default_feed_config') && is_array($feed->getData('default_feed_config'))) {
            $defaultConfig = $feed->getData('default_feed_config');
            if (isset($defaultConfig['ftp']) && is_array($defaultConfig['ftp'])) {
                foreach ($defaultConfig['ftp'] as $field => $value) {
                    $field = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($field))));
                    $_auxiliaryFields[] = $field;
                }
            }
        }
        $this->setAuxiliaryFields($_auxiliaryFields);
        $this->setColumnsNumber(self::DEFAULT_COLUMNS_NUMBER + count($_auxiliaryFields));
    }

    /**
     * Preparing layout, adding buttons
     *
     * @return RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Schedule
     */
    protected function _prepareLayout()
    {
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('googlebasefeedgenerator')->__('Delete'),
                    'class' => 'delete delete-option'
                )));

        $this->setChild('add_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('googlebasefeedgenerator')->__('Add Account'),
                    'class' => 'add',
                    'id'    => 'add_new_ftp_button'
                )));
        return parent::_prepareLayout();
    }

    /**
     * Retrieve HTML of delete button
     *
     * @return string
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }

    /**
     * Retrieve HTML of add button
     *
     * @return string
     */
    public function getAddNewButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    /**
     * Retrieve HTML of test button
     *
     * @return string
     */
    public function getTestButtonHtml($id = false, $hidden = false)
    {
        $id = $id ? $id : '{{id}}';
        $style = $hidden ? 'display:none' : '';
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(
                array(
                    'label'     => Mage::helper('googlebasefeedgenerator')->__('Validate'),
                    'class'     => 'save',
                    'onclick'   => 'ftpAccount.testConnection(\'' . $id . '\')',
                    'style'     => $style,
                    'id'        => 'ftp_test_' . $id,
                )
            )->toHtml();
    }

    /**
     * Retrieve existing accounts
     *
     * @return array
     */
    public function getAccounts()
    {
        if (!$this->hasData('accounts')) {
            if ($this->getFeed()->getId()) {
                $accounts = Mage::getResourceModel('googlebasefeedgenerator/feed_ftp_collection')
                        ->addFeedFilter($this->getFeed()->getId())
                        ->getItems();
                $this->setData('accounts', $accounts);
            } else {
                $this->setData('accounts', array());
            }
        }
        return $this->getData('accounts');
    }

    /**
     * Retrieve input HTML
     * 
     * @param string $name
     * @param boolean $isPassword
     * @param string|boolean $id
     * @param string|boolean $value
     * @param string $style
     * @param string $class
     * @return string
     */
    protected function _getInputHtml($name, $isPassword = false, $id = false, $value = false, $style = '', $class = '')
    {
        $value = $value ? $value : '';
        $id = $id ? $id : '{{id}}';
        $type = $isPassword ? 'password' : 'text';
        $style = empty($style) ? '' : ' style="'. $style. '"';

        return '<input name="ftp[' . $id . '][' . $name . ']" value="' . $value . '" type="' . $type . '" ' .
            ' id="ftp_' . $name . '_' . $id . '" class="input-text ' . $class . '" '. $style.
            ' readonly onfocus="this.removeAttribute(\'readonly\');" />';
    }

    /**
     * Retrieve host HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @return string
     */
    public function getHostHtml($id = false, $value = false)
    {
        return $this->_getInputHtml('host', false, $id, $value, 'width:140px;',
            'validate-ftp-connection required-entry break-ftp-validation');
    }

    /**
     * Retrieve port HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @return string
     */
    public function getPortHtml($id = false, $value = false)
    {
        $value = $value ? $value : '21';
        return $this->_getInputHtml('port', false, $id, $value, 'width:60px;', 'required-entry break-ftp-validation');
    }

    /**
     * Retrieve username HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @return string
     */
    public function getUsernameHtml($id = false, $value = false)
    {
        return $this->_getInputHtml('username', false, $id, $value, 'width:140px;',
            'required-entry break-ftp-validation');
    }

    /**
     * Retrieve password HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @return string
     */
    public function getPasswordHtml($id = false, $value = false)
    {
        if ($value) {
            $value = RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Ftp::OBSCURED_VALUE;
        }
        return $this->_getInputHtml('password', true, $id, $value, '', 'required-entry break-ftp-validation');
    }

    /**
     * Retrieve hidden delete HTML
     * 
     * @param string|boolean $id
     * @return string
     */
    public function getDeleteHiddenHtml($id = false)
    {
        $id = $id ? $id : '{{id}}';
        return '<input type="hidden" class="delete-flag" name="ftp[' . $id . '][delete]" value="" />';
    }

    /**
     * Compose test ftp connection URL
     * 
     * @return string
     */
    public function getTestConnectionUrl()
    {
        return $this->getUrl('*/*/testftp');
    }

    /**
     * Compose auxiliary field name by field
     * 
     * @param string $field
     * @return string
     */
    public function getAuxiliaryFieldName($field)
    {
        return implode(' ', preg_split("/(?<=[a-z])(?![a-z])/", $field, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Retrieve auxiliary fields html by field name
     * 
     * @param string $field
     * @param string|boolean $id
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Ftp $account
     * @return string
     */
    public function getAuxiliaryFieldHtml($field, $id = false, $account = false)
    {
        $value = call_user_func_array(array($this, 'get' . $field . 'Value'), array($account));
        return call_user_func_array(array($this, 'get' . $field . 'Html'), array($id, $value));
    }

    /**
     * Retrieve path HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @return string
     */
    public function getPathHtml($id = false, $value = false)
    {
        return $this->_getInputHtml('path', false, $id, $value, 'width:140px;');
    }

    /**
     * Retrieve path value
     * 
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Ftp|boolean $account
     * @return string
     */
    public function getPathValue($account = false)
    {
        if ($account != false && is_object($account)) {
            return $account->getPath();
        }
        return '';
    }

    /**
     * Retrieve mode HTML
     * 
     * @param string|boolean $id
     * @param string|boolean $value
     * @return string
     */
    public function getModeHtml($id = false, $value = false)
    {
        if (!$this->hasData('mode_select')) {
            $select = $this->getLayout()->createBlock('core/html_select');
            foreach (Mage::getModel('googlebasefeedgenerator/source_ftp')->toArray() as $value => $label) { 
                $select->addOption($value, $label);
            }
            $select->setClass('break-ftp-validation');
            $this->setData('mode_select', $select);
        } else {
            $select = $this->getData('mode_select');
        }
        $id = $id ? $id : '{{id}}';
        $select->setId("ftp_mode_$id");
        $select->setName("ftp[$id][mode]");
        $select->setValue($value);
        return $select->toHtml();
    }

    /**
     * Retrieve mode value
     * 
     * @param RocketWeb_GoogleBaseFeedGenerator_Model_Feed_Ftp|boolean $account
     * @return string
     */
    public function getModeValue($account = false)
    {
        if ($account != false && is_object($account)) {
            return $account->getMode();
        }
        return '';
    }
}
