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
 * @category  RocketWeb
 * @package   RocketWeb_GoogleBaseFeedGenerator
 * @copyright Copyright (c) 201 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Directive_Identifier_Attribute
    extends RocketWeb_GoogleBaseFeedGenerator_Model_Source_Productattributescodes
{
    public function _construct()
    {
        $this->addData(array('param_label' => 'Attribute:', 'param_help' => 'Select an attribute like UPC, EAN or ISBN. When the attribute\'s value is missing for a configurable or grouped item, will fetch the value from lowest priced associated item, and if empty for a bundle, will go for the higherst priced sub-item.'));
        parent::_construct();
    }


    /**
     * @return string
     */
    public function toHtml()
    {
        $html = '<div style="float:left;">'. Mage::helper('googlebasefeedgenerator')->__($this->getData('param_label')). '</div>'
            . '<div style="float:right;"><select name="config[#{field_name}][#{_id}][param]" class="select validate-not-empty" style="width:180px;">';

        $options = $this->toOptionArray();
        foreach ($options as $option) {
            $html .= '<option value="' . $option['value'] . '">' . $option['label'] . '</option>';
        }
        $html .= '</select></div>';

        $html .= '<p class="note" style="clear:both;"><span>' . Mage::helper('googlebasefeedgenerator')->__($this->getData('param_help')) . '</span></p>';
        return $html;
    }
}