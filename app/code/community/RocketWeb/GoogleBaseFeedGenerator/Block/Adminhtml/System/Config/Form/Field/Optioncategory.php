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
 * @copyright Copyright (c) 2015 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Config_Form_Field_Optioncategory
    extends RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Config_Form_Field_Categorytree
{
    public function getLabel() {
        return $this->__('Option multiple rows for:');
    }

    public function getNote() {
        return '<p class="note">'. $this->__('If specified categories here, products outside the selection will be a single row in the feed having the option values comma separated in the column.'). '</p>';
    }

    public function getJsFormObject()
    {
        return 'categories_vary_form';
    }

    /**
     * Set up the widget
     * @return $this|Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('googlebasefeedgenerator/system/config/form/field/categorytree.phtml');

        $tree_block = $this->getLayout()
            ->createBlock('googlebasefeedgenerator/adminhtml_catalog_category_checkboxes_tree')
            ->setHtmlId($this->getHtmlId())
            ->setJsFormObject($this->getJsFormObject());

        $this->setChild('feed_options_categories_tree', $tree_block);
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        return $this;
    }

    protected function _toHtml()
    {
        if(Mage::registry('googlebasefeedgenerator_feed_layout')) {
            $tree_block = $this->getLayout()
                ->createBlock('googlebasefeedgenerator/adminhtml_catalog_category_checkboxes_tree')
                ->setHtmlId($this->getHtmlId())
                ->setJsFormObject($this->getJsFormObject());
            $this->setChild('feed_options_categories_tree', $tree_block);

            $ids = explode(',', $this->getElement()->getValue());
            if (count($ids) == 1 && empty($ids[0])) {
                $ids = null;
            }
            $this->getChild('feed_options_categories_tree')->setCategoryIds($ids);
        }
        return parent::_toHtml();
    }
}