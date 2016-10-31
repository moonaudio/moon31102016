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
class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Import extends Varien_Object
{
    /*
     * Reserved for other content types of import files. For now we work with XML only.
     */
    const XML = 'xml';

    public function toOptionArray()
    {
        $vals = array(
            self::XML => Mage::helper('googlebasefeedgenerator')->__('Feed Configuration'),
        );
        $options = array();
        foreach ($vals as $k => $v) {
            $options[] = array('value' => $k, 'label' => $v);
        }
        return $options;
    }

    public function toArray(array $arrAttributes = array())
    {
        $vals = array(
            self::XML => Mage::helper('googlebasefeedgenerator')->__('XML'),
        );
        $options = array();
        foreach ($vals as $k => $v) {
            $options[$k] = $v;
        }

        return $options;
    }

}