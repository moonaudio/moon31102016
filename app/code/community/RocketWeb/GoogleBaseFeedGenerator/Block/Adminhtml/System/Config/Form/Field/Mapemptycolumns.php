<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category  RocketWeb
 * @package   RocketWeb_GoogleBaseFeedGenerator
 * @copyright Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */


/**
 * Adminhtml system config attributes array field renderer
 *
 * @category RocketWeb
 * @package  RocketWeb_GoogleBaseFeedGenerator
 */
class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Config_Form_Field_Mapemptycolumns
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Config_Form_Field_Mapproductcolumns
{

    public function __construct($attributes=array())
    {
        parent::__construct();

        $this->setData($attributes);

        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Rule');

        // overwrite labels defined in parent::
        $this->addColumn(
            'column', array(
                'label' => Mage::helper('adminhtml')->__('Empty column'),
                'style' => 'width:200px',
            )
        );

        $this->addColumn(
            'attribute', array(
                'label' => Mage::helper('adminhtml')->__('Replace with'),
                'style' => 'width:300px',
            )
        );

        // Set parameters to be replaced in the frontend row template
        $this->setGroupName('filters');
        $this->setFieldName('filters_map_replace_empty_columns');

        if(!$this->getName() && $this->getFieldName()) {
            $this->setName($this->getFieldName());
        }
        if(!$this->getElement()) {
            $this->setElement($this);
        }
    }

    /**
     * @param $columnName
     * @return string
     */
    protected function _renderColumnCell($columnName)
    {
        $column = $this->_columns[$columnName];
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        $html = '<select name="' . $inputName . '" ' . (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '>';
        foreach ($this->getMapColumns() as $column) {
            if (!empty($column['value'])) {
                $html .= '<option label="' . $column['label'] . '" value="' . $column['value'] . '">' . $column['label'] . '</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }
}