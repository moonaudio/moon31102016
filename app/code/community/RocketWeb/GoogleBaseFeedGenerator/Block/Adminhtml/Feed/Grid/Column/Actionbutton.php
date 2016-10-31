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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Grid_Column_Actionbutton extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    /**
     * Renders column
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $buttons = array();
        $actions = $this->getColumn()->getActions();
        if ( empty($actions) || !is_array($actions) ) {
            return '&nbsp;';
        }

        // load config from xml
        $row->load($row->getId());

        foreach ($actions as $action) {
            if ( is_array($action) ) {
                if (array_key_exists('rowCallback', $action)) {
                    $action['url'] = call_user_func_array(array($row, $action['rowCallback']), array());
                    unset($action['rowCallback']);
                }
                $buttons[] = $this->_toLinkHtml($action, $row);
            }
        }

        $out = implode(' &nbsp;/&nbsp; ', $buttons);
        return $out;
    }
}
