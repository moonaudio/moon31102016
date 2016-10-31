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
 * @copyright Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_Form_Element_Abstract extends Varien_Data_Form_Element_Abstract
{
    /**
     * @var defined the block xpath which is used to render html
     */
    protected $_block;

    /**
     * @return mixed|string
     */
    public function getHtml()
    {
        $block = $this->getRenderer()->getLayout()->createBlock($this->_block)
                ->setElement($this)
                ->setLabel($this->getLabel())
                ->setId($this->getId())
                ->setAfterElementHtml($this->getAfterElementHtml())
                ->setForm($this->getForm());

        return $block->toHtml();
    }
}