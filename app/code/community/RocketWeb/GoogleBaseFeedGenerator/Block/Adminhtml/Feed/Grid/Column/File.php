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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Feed_Grid_Column_File extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    /**
     * Renders column
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        // load config from xml
        $row->load($row->getId());
        $progress = $row->getMessages();

        $out = '';
        $filepath = Mage::getBaseDir(). DS. $row->getConfig('general_feed_dir'). DS. $row->getFeedFile();
        if (file_exists($filepath)) {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB). $row->getConfig('general_feed_dir'). DS. $row->getFeedFile();
            $out .= '<a href="'.$url . '" target="_blank">'. $url. '</a><br />'
            . sprintf($this->__('%d added, %d skipped at %s'), $progress['added'], $progress['skipped'], $progress['date']);

        }
        return $out;
    }
}
