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
 * @copyright Copyright (c) 2016 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Directive_ItemGroupId extends Varien_Object
{

    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'entity_id', 'label' => Mage::helper('googlebasefeedgenerator')->__('id')),
            array('value' => 'sku', 'label' => Mage::helper('googlebasefeedgenerator')->__('sku')),
        );
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $html = '<div style="float:left;">'. Mage::helper('googlebasefeedgenerator')->__('Base on parent attribute:'). '</div>'
            . '<div style="float:right;"><select name="config[#{field_name}][#{_id}][param]" class="select" style="width:180px;">';

        $options = $this->toOptionArray();
        foreach ($options as $option) {
            $html .= '<option value="' . $option['value'] . '">' . $option['label'] . '</option>';
        }
        $html .= '</select></div>';

        $html .= '<p class="note" style="clear:both;"><span>' . Mage::helper('googlebasefeedgenerator')->__('Will output the same value for all associated items, and empty for the complex product. The value is computed using the parent product attribute specified, and sufix of variant attributes not mapped in the columns.');

        return $html;
    }
}