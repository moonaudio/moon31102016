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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_Abstract extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * @param $elementId
     * @param $type
     * @param $config
     * @param bool|false $after
     * @return mixed
     */
    public function addField($elementId, $type, $config, $after=false)
    {
        $feed = Mage::registry('googlebasefeedgenerator_feed');
        $section = strtolower(str_replace('RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Edit_Tab_', '', get_class($this)));
        if ($feed->isAllowedConfig($section, str_replace($section.'_', '', $elementId))) {
            return $this->getFieldset()->addField($elementId, $type, $config, $after);
        }
        return false;
    }
}