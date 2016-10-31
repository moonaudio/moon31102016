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
 * @copyright  Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_Core_Config_Element extends Mage_Core_Model_Config_Element
{

    const DEFAULT_COLUMN_MAP_KEY = 'default_map_product_columns';

    /**
     * Returns the node and children as an array
     *
     * @param bool $isCanonical - whether to ignore attributes
     * @return array|string
     */
    protected function _asArray($isCanonical = false)
    {
        $result = array();
        if (!$isCanonical) {
            // add attributes
            foreach ($this->attributes() as $attributeName => $attribute) {
                if ($attribute) {
                    $result['@'][$attributeName] = (string)$attribute;
                }
            }
        }
        // add children values
        if ($this->hasChildren()) {
            foreach ($this->children() as $childName => $child) {
                if ($childName == self::DEFAULT_COLUMN_MAP_KEY) {
                    $result[$childName] = $child->parseDefaultMapProductColumns($isCanonical);
                } else {
                    $result[$childName] = $child->_asArray($isCanonical);
                }
            }
        } else {
            if (empty($result)) {
                // return as string, if nothing was found
                $result = (string) $this;
            } else {
                // value has zero key element
                $result[0] = (string) $this;
            }
        }
        return $result;
    }

    /**
     * Returns the node and children as an array. Same as _asArray, it will not overwrite XML tags with the same name.
     * It will create an array for each new XML tag name and if that tag is found multiple times, it will add the new
     * children to the already existing array. This allows the <default_map_product_columns> elements to declare columns
     * using the same directives.
     *
     * @param bool $isCanonical - whether to ignore attributes
     * @return array|string
     */
    protected function parseDefaultMapProductColumns($isCanonical = false)
    {
        $result = array();
        if (!$isCanonical) {
            // add attributes
            foreach ($this->attributes() as $attributeName => $attribute) {
                if ($attribute) {
                    $result['@'][$attributeName] = (string)$attribute;
                }
            }
        }
        // add children values
        if ($this->hasChildren()) {
            foreach ($this->children() as $childName => $child) {
                if (!isset($result[$childName])) {
                    $result[$childName] = array();
                }
                $result[$childName][] = $child->_asArray($isCanonical);
            }
        } else {
            if (empty($result)) {
                // return as string, if nothing was found
                $result = (string) $this;
            } else {
                // value has zero key element
                $result[0] = (string) $this;
            }
        }
        return $result;
    }
}
